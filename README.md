# EventFlow — API (back-end)

Sistema web de **gerenciamento e divulgação de eventos**. Este repositório é o **back-end**: uma API REST em
**Laravel 13 + MySQL**. O front-end (Vue 3) fica em `EventFlow-web-frontend`.

> Guia de apoio para a apresentação: [`docs/GUIA-DE-DEFESA.md`](docs/GUIA-DE-DEFESA.md)

## Por que Laravel

- Framework **MVC** completo (Eloquent ORM, migrations, validação, autenticação, upload de arquivos).
- Já conhecido do dia a dia de trabalho, o que reduz risco e permite explicar cada parte do código.
- Ecossistema pronto para o que o trabalho pede: **Sanctum** (login por token), **Storage** (upload) e o
  pacote **DomPDF** (geração de PDF).
- **MySQL** como banco relacional: os eventos, inscrições, categorias e locais têm relacionamentos bem definidos.

## Requisitos do trabalho × onde estão

| Requisito | Onde |
| --- | --- |
| CRUD completo de eventos | `EventoController`, `EventoRequest`, `EventoResource`, `routes/api.php` |
| Categorias e Locais (listar, criar, editar, excluir) | `CategoriaController`, `LocalController` |
| Participantes / inscrições | `InscricaoController`, model `Inscricao` (tabela `inscricoes`) |
| Data, horário, endereço, descrição, preço e vagas | migration `create_eventos_table` |
| Vagas controladas (esgotado, duplicidade, evento encerrado) | `InscricaoController@store` |
| Upload e armazenamento do banner | `EventoController` (`Storage`, disco `public`) |
| Banner exibido na página de detalhes | `EventoResource` → `banner_url` |
| Geração e download de PDF (ingresso) | `InscricaoController@ingresso` + `resources/views/pdf/ingresso.blade.php` |
| Local com latitude/longitude (mapa) | model `Local` (o mapa e a busca de endereço ficam no front) |
| Autenticação (login/logout, sessão) | `AuthController` + Laravel Sanctum (token Bearer) |
| Validação dos dados | `app/Http/Requests/*` (mensagens em `lang/pt_BR`) |
| Banco de dados funcional | migrations + seeders (dados de demonstração) |

## Arquitetura MVC

| Camada | Pasta | Papel |
| --- | --- | --- |
| **Model** | `app/Models/*`, `database/migrations`, `database/factories` | Entidades, relacionamentos e esquema do banco |
| **Controller** | `app/Http/Controllers/Api/*`, `app/Http/Requests/*`, `routes/api.php` | Regras da aplicação, validação e resposta |
| **View** | `app/Http/Resources/*` (JSON) + o **SPA Vue** | A "visão" da API é o JSON formatado pelos Resources; a interface web é o front-end Vue |

Fluxo de uma requisição: `routes/api.php` → *Controller* → *FormRequest* (valida) → *Model* (banco) → *Resource*
(formata o JSON) → resposta.

```
app/
├── Models/                 Evento · Categoria · Local · Inscricao · User
├── Http/
│   ├── Controllers/Api/    EventoController · CategoriaController · LocalController · InscricaoController · AuthController · ProfileController
│   ├── Requests/           EventoRequest · CategoriaRequest · LocalRequest · InscricaoRequest · ProfileUpdateRequest
│   └── Resources/          EventoResource · CategoriaResource · LocalResource · InscricaoResource
database/
├── migrations/             uma por tabela
├── factories/              dados falsos (testes e seeders)
└── seeders/                DatabaseSeeder · CategoriaSeeder · LocalSeeder · EventoSeeder
resources/views/pdf/        ingresso.blade.php (modelo do PDF)
routes/api.php              todas as rotas da API
lang/pt_BR/                 mensagens de validação e autenticação em português
tests/Feature/              testes de cada recurso
```

## Modelo de dados

```
categorias 1 ──< eventos >── 1 locais
                   │
                   1
                   │
                   └──< inscricoes
```

| Tabela | Colunas principais |
| --- | --- |
| `categorias` | `id`, `nome` (único) |
| `locais` | `id`, `nome`, `latitude`, `longitude`, `endereco` (opcional) |
| `eventos` | `id`, `nome`, `descricao`, `data_evento`, `hora_evento`, `preco`, `endereco` (opcional), `vagas`, `banner` (caminho do arquivo), `categoria_id`→categorias, `local_id`→locais |
| `inscricoes` | `id`, `codigo` (UUID do ingresso), `evento_id`→eventos, `nome`, `email`, `documento` (opcional) — único por (`evento_id`,`email`) |
| `users` / `personal_access_tokens` | usuários do painel e tokens de login (Sanctum) |

Regras de integridade: não é possível excluir categoria ou local que tenha eventos; ao excluir um evento, as
inscrições e o arquivo do banner são removidos.

## Endpoints (`/api`)

Enviar `Accept: application/json`. Rotas protegidas exigem `Authorization: Bearer <token>`.

| Método | Rota | Acesso | Função |
| --- | --- | --- | --- |
| POST | `/register` · `/login` | público | Criar conta / entrar → `{ token, user }` |
| POST | `/logout` | logado | Encerra o token atual |
| GET · PATCH | `/user` · `/user/profile` | logado | Dados do usuário / atualizar nome e e-mail |
| GET | `/eventos` | público | Lista paginada. Filtros: `busca`, `categoria_id`, `local_id`, `por_pagina`, `page` |
| GET | `/eventos/{id}` | público | Detalhes (categoria, local, banner, vagas restantes) |
| POST | `/eventos` | logado | Cria evento (`multipart/form-data`, com `banner`) |
| PUT | `/eventos/{id}` | logado | Edita (no `multipart`, enviar `POST` com `_method=PUT`; `remover_banner=1` remove a imagem) |
| DELETE | `/eventos/{id}` | logado | Exclui evento (e banner/inscrições) |
| GET | `/categorias` · `/locais` | público | Listagem |
| POST · PUT · DELETE | `/categorias` · `/locais` (`/{id}`) | logado | Cadastro de apoio |
| POST | `/eventos/{id}/inscricoes` | público | Inscreve um participante → devolve o `codigo` do ingresso |
| GET | `/eventos/{id}/inscricoes` | logado | Lista os participantes do evento |
| DELETE | `/inscricoes/{codigo}` | logado | Cancela uma inscrição |
| GET | `/inscricoes/{codigo}/ingresso` | público (pelo código) | **Baixa o ingresso em PDF** |

O ingresso é acessado pelo **código UUID** (não pelo id), então um ingresso não pode ser "adivinhado".

## Bibliotecas e a função de cada uma

| Biblioteca | Função no sistema |
| --- | --- |
| `laravel/framework` 13 | Framework MVC: rotas, Eloquent (ORM), validação, storage, testes |
| `laravel/sanctum` | Autenticação por **token Bearer** (login/logout do painel) |
| `barryvdh/laravel-dompdf` (Dompdf) | **Gera o PDF** do ingresso a partir de um template Blade |
| Laravel `Storage` (disco `public`) | **Upload e armazenamento** do banner (`storage/app/public/banners`) |
| Laravel `FormRequest` | **Validação** dos formulários (regras + mensagens em português) |
| MySQL 8.4 (via Laravel Sail/Docker) | Banco de dados relacional |
| PHPUnit, Larastan (PHPStan), Pint | Testes automatizados, análise estática e padronização de código |

## Como rodar

Com Docker (Laravel Sail), na pasta do back-end:

```bash
docker compose up -d
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan migrate:fresh --seed   # cria tabelas + dados de demonstração
docker compose exec laravel.test php artisan storage:link --force    # necessário para exibir os banners
```

A API fica em `http://localhost`. Usuário de demonstração: **test@example.com** / **password**.
Configure `FRONTEND_URL` no `.env` (padrão `http://localhost:5173`): ela libera o CORS para o front-end.

Sem Docker: `composer setup` e `php artisan serve` (API em `http://localhost:8000`, com SQLite).

## Qualidade

```bash
composer ci:check    # Pint (estilo) + PHPStan nível 7 + 57 testes (PHPUnit)
```

Os testes cobrem: CRUD de categorias, locais e eventos, upload/troca/remoção de banner, filtros e paginação,
inscrição (vagas esgotadas, e-mail duplicado, evento encerrado), geração do PDF, autenticação e regras de acesso.
