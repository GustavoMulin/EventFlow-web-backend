# Guia de defesa — EventFlow

Roteiro para a apresentação: o que demonstrar, como explicar, onde mexer se pedirem uma alteração ao vivo e
respostas curtas para as perguntas mais prováveis.

## 1. Roteiro de demonstração (≈ 10 min)

Antes: containers no ar, `migrate:fresh --seed` executado, front em `http://localhost:5173`.

1. **Visitante** abre `/eventos`: lista com miniatura, categoria, data, preço, vagas; testar a **busca** e o filtro de categoria; paginação.
2. **Mapa** (`/mapa`): marcadores por local → clicar → popup (nome, data, preço) → **Ver detalhes**.
3. **Detalhe** do evento: banner, informações, mapa do local.
4. **Inscrição** (sem login): preencher nome/e-mail → confirmação com código → **Baixar ingresso (PDF)** → abrir o PDF.
   Mostrar o evento **"Hackathon EventFlow"** (lotado): "vagas esgotadas". Tentar o mesmo e-mail duas vezes: bloqueia.
5. **Login** (`test@example.com` / `password`) → aparece o menu **Categorias / Locais / Perfil**.
6. **CRUD de Categoria** (criar, editar, excluir) e tentar excluir uma categoria com eventos (bloqueia).
7. **CRUD de Local**: novo local → digitar endereço → **Buscar no mapa** (API Nominatim) → marcador → ajustar arrastando → salvar.
8. **CRUD de Evento**: **Novo evento** (categoria, local, foto de banner) → aparece na lista com miniatura → **Editar** (trocar/remover banner) → **Excluir**.
9. No detalhe (logado): **lista de participantes** → baixar PDF de um inscrito → cancelar inscrição.
10. **Perfil**: dados do usuário, **Sair**.
11. Mostrar o **banco no DBeaver** (`eventos`, `inscricoes`, `locais`, `categorias`) e a pasta `storage/app/public/banners`.

## 2. Explicando o fluxo de uma requisição (exemplo: criar evento)

1. `EventoFormView.vue` monta um `FormData` (campos + arquivo) e chama `services/eventos.js` → `criarEvento`.
2. O `axios` (`lib/api.js`) envia `POST /api/eventos` com o token no header `Authorization: Bearer …`.
3. `routes/api.php`: a rota está no grupo `auth:sanctum` → só usuário logado.
4. `EventoController@store` recebe um **`EventoRequest`**, que **valida** (`app/Http/Requests/EventoRequest.php`).
5. O banner é gravado com `store('banners', 'public')` e o caminho vai para a coluna `eventos.banner`.
6. `Evento::create(...)` grava no MySQL (**Model**).
7. A resposta passa pelo **`EventoResource`**, que decide o JSON (inclui `banner_url`, `vagas_restantes`).
8. O front recebe e redireciona para a página de detalhes.

**MVC:** Model = `app/Models`; Controller = `Controllers/Api` (+ Requests); View = SPA Vue (o JSON dos Resources
é a "camada de apresentação" da API).

## 3. "Altere isso ao vivo" — onde mexer

### Adicionar um campo ao evento (ex.: `organizador`)
1. **Migration**: nova migration `add_organizador_to_eventos_table` (ou editar `create_eventos_table` e rodar `migrate:fresh --seed`).
2. **Model** `Evento.php`: incluir `'organizador'` em `#[Fillable([...])]`.
3. **Request** `EventoRequest.php`: `'organizador' => ['nullable', 'string', 'max:150']`.
4. **Resource** `EventoResource.php`: `'organizador' => $this->organizador`.
5. **Front** `EventoFormView.vue`: adicionar `organizador` em `form` e um `<UiField>`; exibir em `EventoDetalheView.vue`.
6. (Opcional) `lang/pt_BR/validation.php` → `attributes` para o nome amigável.

### Mudar o tamanho máximo do banner
- Backend: `EventoRequest.php` → `'max:2048'` (KB) e a mensagem `banner.max`.
- Front: `components/ui/UiImageUpload.vue` → constante `TAMANHO_MAXIMO`.

### Mudar o conteúdo/visual do PDF
- `resources/views/pdf/ingresso.blade.php` (HTML + CSS simples; o Dompdf não suporta flex/grid, por isso é tabela).

### Nova regra de negócio nas inscrições (ex.: no máximo 1 inscrição por documento)
- `InscricaoController@store`, dentro da transação: adicionar a checagem e lançar
  `ValidationException::withMessages([...])` (padrão das regras que já existem).

### Novo filtro na lista (ex.: por local)
- Já existe `local_id` na API (`EventoController@index`). No front: `EventosView.vue` → carregar `listarLocais()` e
  adicionar um `<UiSelect>` igual ao de categoria.

### Mudar cores
- Classes do Tailwind nos componentes (`indigo-600` é a cor principal). Trocar por outra cor (ex.: `emerald-600`).

### Mostrar dados/rotas rapidamente
```bash
docker compose exec laravel.test php artisan route:list --path=api    # todas as rotas
docker compose exec laravel.test php artisan tinker                     # ex.: \App\Models\Evento::with('inscricoes')->first()
docker compose exec laravel.test php artisan migrate:fresh --seed      # zera e recria o banco
docker compose exec laravel.test php artisan test                      # roda os testes
```

## 4. Perguntas prováveis

- **Por que Laravel / Vue?** Laravel: MVC completo (ORM, validação, upload, testes) e é o que uso no trabalho. Vue 3: componentes reativos, rotas e estado bem organizados, e é o padrão da turma.
- **Por que dois projetos (API + SPA)?** Separação de responsabilidades: o back-end só expõe dados (JSON); o front-end é a interface. O contrato entre eles é a API.
- **Como funciona o login?** `POST /api/login` valida e-mail/senha e devolve um **token Sanctum**; o front guarda no `localStorage` e envia em `Authorization: Bearer`. `logout` revoga o token.
- **O que é um Resource?** Classe que transforma o Model no JSON de saída (ex.: formata data/hora, monta `banner_url`, calcula `vagas_restantes`).
- **O que é um FormRequest?** Classe que concentra a **validação** de uma rota antes de chegar ao Controller.
- **Por que `POST` + `_method=PUT` na edição?** O PHP não lê `multipart/form-data` em requisições `PUT`. O Laravel aceita `_method=PUT` num `POST` e trata como `PUT`.
- **Onde ficam as imagens?** Em `storage/app/public/banners`; o `php artisan storage:link` cria `public/storage` apontando para lá, e o banco guarda só o **caminho**.
- **Como o PDF é gerado?** O Dompdf renderiza a view Blade `pdf/ingresso.blade.php` e o Laravel devolve como download. O front pede como *blob* e dispara o download.
- **Por que o ingresso usa UUID?** Para o link do PDF não ser "adivinhável" (não dá para trocar o id na URL e ver o ingresso de outra pessoa).
- **Como evita ultrapassar as vagas?** A inscrição roda numa **transação** com `lockForUpdate` no evento: duas inscrições simultâneas não passam do limite.
- **Como o mapa funciona?** **Leaflet** desenha o mapa com os *tiles* do **OpenStreetMap**. Cada evento tem um `Local` com latitude/longitude → um marcador por local.
- **Como busca o endereço?** A tela de Locais consulta a API pública **Nominatim** (OpenStreetMap) que converte texto em latitude/longitude (geocodificação).
- **Por que os nomes em português?** O domínio (Evento, Categoria, Local, Inscrição) usa o vocabulário do trabalho; nomes do framework/infra (User, Auth, api.js) ficam no padrão.
- **Por que `data_evento` e não `data`?** A resposta da API é embrulhada em `{ "data": … }`; um campo chamado `data` colidia com esse envelope.

## 5. Mapa de arquivos importantes

| Assunto | Back-end | Front-end |
| --- | --- | --- |
| Eventos | `EventoController`, `EventoRequest`, `EventoResource`, `Evento` | `views/eventos/*`, `services/eventos.js`, `components/eventos/EventoCard.vue` |
| Categorias | `CategoriaController`, `CategoriaRequest` | `views/categorias/CategoriasView.vue`, `services/categorias.js` |
| Locais / busca de endereço | `LocalController`, `LocalRequest` | `views/locais/LocaisView.vue`, `services/geocodificacao.js`, `components/mapa/MapaLocal.vue` |
| Mapa de eventos | (usa `Local` do evento) | `views/mapa/MapaView.vue`, `components/mapa/MapaEventos.vue`, `lib/leaflet.js` |
| Inscrição + PDF | `InscricaoController`, `pdf/ingresso.blade.php` | `EventoDetalheView.vue`, `services/inscricoes.js` |
| Login / sessão | `AuthController`, Sanctum | `stores/auth.js`, `lib/api.js`, `router/index.js` (guards) |
