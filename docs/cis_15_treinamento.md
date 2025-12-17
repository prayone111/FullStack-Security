Gestão de Provedores de Serviço (CIS Control 15)

Data de Revisão: 10/12/2025
Responsável: [Alex]

Este documento lista os serviços de terceiros utilizados na infraestrutura do FullStack Security e a avaliação de segurança realizada.

1. Provedores Ativos

A. Cloudflare

Serviço: Cloudflare Zero Trust (Tunnels).

Função: Prover conectividade segura (HTTPS) e proteção DDoS sem a necessidade de abrir portas no firewall local (Inbound Ports).

Justificativa de Segurança:

"Utilizo Cloudflare Tunnel para garantir Zero Trust Network Access, removendo a necessidade de expor o IP real do servidor e mitigando ataques diretos à infraestrutura."

Classificação de Risco: Crítico (Infraestrutura de Rede).

Monitoramento: Logs de acesso monitorados via painel Cloudflare e logs locais do cloudflared.

B. Provedor de Virtualização (Hypervisor)

Serviço: [VirtualBox / VMware / Outro]

Função: Hospedagem da Máquina Virtual Linux.

Segurança: Isolamento da rede da VM em modo "Bridge" ou "NAT", garantindo que falhas no servidor não afetem o host principal diretamente.

2. Política de Avaliação

Novos provedores só serão adicionados se oferecerem:

Autenticação Multifator (MFA) para o painel administrativo.

Logs de auditoria acessíveis.

Certificações de segurança (SOC2, ISO 27001) reconhecidas.
