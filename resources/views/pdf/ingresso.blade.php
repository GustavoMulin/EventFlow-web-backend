<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Ingresso {{ $evento->nome }}</title>
    <style>
        @page { margin: 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        .topo { background: #4f46e5; color: #ffffff; }
        .topo td { padding: 18px 22px; }
        .marca { font-size: 22px; font-weight: bold; }
        .tipo { text-align: right; font-size: 13px; }
        .caixa { border: 1px solid #cbd5e1; margin-top: 18px; }
        .caixa td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .caixa tr:last-child td { border-bottom: none; }
        .titulo-evento { font-size: 20px; font-weight: bold; padding: 16px 14px !important; }
        .rotulo { width: 28%; color: #64748b; font-size: 11px; text-transform: uppercase; }
        .secao { margin-top: 22px; font-size: 13px; font-weight: bold; color: #4f46e5; text-transform: uppercase; }
        .codigo { margin-top: 26px; border: 2px dashed #4f46e5; text-align: center; padding: 16px; }
        .codigo .valor { font-size: 15px; font-weight: bold; letter-spacing: 1px; margin-top: 6px; }
        .rodape { margin-top: 26px; text-align: center; color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <table class="topo">
        <tr>
            <td class="marca">EventFlow</td>
            <td class="tipo">INGRESSO / COMPROVANTE DE INSCRIÇÃO</td>
        </tr>
    </table>

    <table class="caixa">
        <tr>
            <td colspan="2" class="titulo-evento">{{ $evento->nome }}</td>
        </tr>
        <tr>
            <td class="rotulo">Data</td>
            <td>{{ $evento->data_evento->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="rotulo">Horário</td>
            <td>{{ substr($evento->hora_evento, 0, 5) }}</td>
        </tr>
        <tr>
            <td class="rotulo">Local</td>
            <td>{{ $evento->local->nome }}</td>
        </tr>
        <tr>
            <td class="rotulo">Endereço</td>
            <td>{{ $evento->endereco ?? $evento->local->endereco ?? 'Não informado' }}</td>
        </tr>
        <tr>
            <td class="rotulo">Categoria</td>
            <td>{{ $evento->categoria->nome }}</td>
        </tr>
        <tr>
            <td class="rotulo">Valor</td>
            <td>
                @if ((float) $evento->preco > 0)
                    R$ {{ number_format((float) $evento->preco, 2, ',', '.') }}
                @else
                    Gratuito
                @endif
            </td>
        </tr>
    </table>

    <div class="secao">Participante</div>
    <table class="caixa">
        <tr>
            <td class="rotulo">Nome</td>
            <td>{{ $inscricao->nome }}</td>
        </tr>
        <tr>
            <td class="rotulo">E-mail</td>
            <td>{{ $inscricao->email }}</td>
        </tr>
        @if ($inscricao->documento)
            <tr>
                <td class="rotulo">Documento</td>
                <td>{{ $inscricao->documento }}</td>
            </tr>
        @endif
        <tr>
            <td class="rotulo">Inscrição em</td>
            <td>{{ $inscricao->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="codigo">
        <div>CÓDIGO DO INGRESSO</div>
        <div class="valor">{{ strtoupper($inscricao->codigo) }}</div>
    </div>

    <div class="rodape">
        Apresente este ingresso na entrada do evento. Documento gerado automaticamente pelo EventFlow.
    </div>
</body>
</html>
