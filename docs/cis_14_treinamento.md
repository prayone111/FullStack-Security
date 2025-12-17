Registro de Treinamento e Competências em Segurança (CIS Control 14)

Profissional: [Alex Jesus]
Projeto: FullStack Security Lab
Data de Atualização: 10/12/2025

Este documento serve como evidência de conformidade com o CIS Control 14 (Security Awareness and Skills Training), demonstrando a aquisição de competências teóricas e a minha aplicação prática em ambiente laboratorial.

1. Treinamentos Formais Realizados

Curso / Certificação: Segurança Cibernética: Controles 1 a 18 do CIS Controls

Instituição: Escola Virtual de Governo (EV.G)

Data de Conclusão: 25/07/2025

Foco Principal: Implementação e auditoria dos 18 Controles de Segurança Crítica (CIS v8).
--------------------------------------------------------------------------------------------

Curso / Certificação: Linux Hardening & Security

Instituição: Laboratório Prático (Autoestudo)

Data de Conclusão: Dez/2025

Foco Principal: Proteção de servidores Linux, SSH e Firewalls.
--------------------------------------------------------------------------------------------

Curso / Certificação: Aplicação de Controles CIS v8

Instituição: Projeto Prático (FullStack Security)

Data de Conclusão: Dez/2025

Foco Principal: Implementação técnica dos 18 controles CIS em ambiente real.
--------------------------------------------------------------------------------------------

Implementação técnica dos 18 controles CIS.

2. Competências Técnicas Aplicadas (On-the-Job Training)

Durante o desenvolvimento da infraestrutura deste projeto, as seguintes competências de segurança foram treinadas e validadas na prática:

🛡️ Segurança de Infraestrutura (Infrastructure Security)

[x] Linux Hardening: Configuração segura de permissões, remoção de serviços desnecessários e gestão de usuários (CIS 4, 5).

[x] Firewall Management: Implementação e gestão de regras de entrada/saída com UFW (CIS 12).

[x] Zero Trust Network Access: Configuração de túneis seguros com Cloudflare Tunnel para ocultar o IP de origem e forçar HTTPS (CIS 15).

[x] Prevenção de Intrusão: Configuração do Fail2Ban para mitigar ataques de força bruta no SSH (CIS 13).

💻 Segurança de Aplicação (AppSec)

[x] Secure Coding (PHP): Implementação de Prepared Statements para prevenir SQL Injection (CIS 16).

[x] Output Encoding: Uso de funções de sanitização (htmlspecialchars) para mitigar XSS (CIS 16).

[x] Security Headers: Configuração do Apache para prevenir Clickjacking (X-Frame-Options: DENY) e vazamento de versão (CIS 9).

[x] Gestão de Erros: Supressão de erros detalhados no php.ini (expose_php = Off, display_errors = Off) para evitar Information Disclosure.

🔍 Auditoria e Monitoramento (Blue Team)

[x] Detecção de Malware: Instalação e tuning (ajuste fino) do Rootkit Hunter (rkhunter), com criação de whitelists para evitar falsos positivos (CIS 10).

[x] Integridade de Arquivos: Verificação de binários do sistema com debsums (CIS 16).

[x] Análise de Logs: Monitoramento em tempo real de logs de acesso e erro do Apache (/var/log/apache2/) para detecção de anomalias (CIS 8).

[x] Auditoria Automatizada: Execução e análise de relatórios do Lynis para identificar falhas de conformidade (CIS 7).

3. Simulações de Ataque e Defesa (Purple Teaming)

Para validar a eficácia dos controles, foram realizadas simulações de ataque contra a própria infraestrutura (em conformidade com o CIS 18):

Cenário 1: Tentativa de Brute Force no SSH.

Resultado: O IP atacante foi banido automaticamente pelo Fail2Ban após 5 tentativas.

Cenário 2: Tentativa de SQL Injection no Login.

Resultado: O ataque falhou devido ao uso de Prepared Statements; o log de erro não expôs dados do banco.

Cenário 3: Scan de Portas (Nmap).

Resultado: Apenas as portas autorizadas (80, 22) estavam visíveis; portas de banco de dados (3306) estavam bloqueadas externamente.

4. Declaração de Manutenção de Conhecimento

Comprometo-me a manter estas competências atualizadas através de:

Acompanhamento de boletins de segurança (CVEs) para Apache e Linux.

Execução periódica de scans de vulnerabilidade (Lynis/Rkhunter).

Prática contínua em laboratórios de PenTest (Hack The Box / TryHackMe).
