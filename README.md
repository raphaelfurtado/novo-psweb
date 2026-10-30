# PSWeb (nova versão)

Sistema de gestão financeira da associação de moradores: mensalidades, despesas, folha dos funcionários e prestação de contas.

O PSWeb original foi feito em CodeIgniter 4 e funcionou bem por bastante tempo, mas mexer nele estava ficando caro. Esta versão é uma reescrita em Laravel 12 + Livewire 3 + Tailwind + Alpine, usando o mesmo banco MySQL do sistema antigo. Ou seja: ninguém precisou migrar dados nem trocar senha. Os dois sistemas conseguem rodar lado a lado.

## Quem usa

São dois tipos de usuário, e os dois entram com telefone e senha.

O administrador cuida de tudo: cadastra moradores e funcionários, lança pagamentos e despesas, gera as mensalidades do ano, importa o extrato do banco e tira os relatórios para a reunião.

O morador vê os próprios pagamentos, baixa comprovantes e acompanha o portal da transparência, que mostra mês a mês quanto a associação arrecadou (portaria, 13º dos funcionários) e quantas casas já pagaram. A ideia é que qualquer um consiga conferir para onde vai o dinheiro sem precisar pedir planilha para ninguém.

## O que dá pra fazer

- Cadastro de moradores (com quadra e casa), funcionários, recebedores e formas e tipos de pagamento
- Lançamento de pagamentos, saídas e folha, com anexo de comprovante
- Geração de mensalidades em lote, escolhendo o ano, as competências, o valor e o dia de vencimento
- Conciliação bancária: você sobe o `.ofx` do banco, o sistema lê os créditos e sugere de qual morador e de qual parcela é cada um, e você confirma
- Acordos para quem está devendo, juntando parcelas em atraso
- Relatórios de fluxo de caixa, inadimplência, folha, prestação de contas, extrato do morador, demonstrativo anual e outros, todos exportáveis em PDF e Excel
- Dashboard com gráficos, tema claro e escuro, e funciona no celular
