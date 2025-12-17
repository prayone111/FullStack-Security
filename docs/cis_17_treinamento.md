===================================================================
PLANO DE RESPOSTA A INCIDENTES (IRP)
Projeto: FullStack Security

[1] IDENTIFICAÇÃO (Como saber se fui invadido?)

Sinais de Alerta:

Site lento ou fora do ar (HTTP 500/502).

Uso de CPU em 100% (comando 'top').

Logs estranhos em /var/log/auth.log (muitas tentativas de SSH).

Arquivos novos ou modificados na pasta /var/www/html.

Alertas do Rkhunter ou Lynis.

[2] CONTENÇÃO (Estancar o sangramento)

Se confirmar um ataque, executo IMEDIATAMENTE nesta ordem:

A. CORTAR O ACESSO EXTERNO (A "Bomba Atômica")
Comando: pkill cloudflared
(Isso derruba o site instantaneamente, impedindo roubo de dados).

B. BLOQUEAR O ATACANTE (Se eu souber o IP)
Comando: sudo ufw deny from [IP_DO_ATACANTE] to any

C. ISOLAR O BANCO DE DADOS
Comando: sudo systemctl stop mariadb

[3] ERRADICAÇÃO (Limpar a sujeira)

A. Matar processos suspeitos
Comando: kill -9 [PID_DO_PROCESSO]

B. Trocar todas as senhas

Senha do usuário Linux (prayone111/alexjsus).

Senha do Banco de Dados.

C. Verificar integridade dos arquivos
Comando: sudo debsums -c
Comando: sudo rkhunter --check --rwo

[4] RECUPERAÇÃO (Voltar a funcionar)

A. Restaurar o Banco de Dados (se foi corrompido)
Comando: mysql -u prayone111 -p meubanco < backup_fullstack.sql

B. Reiniciar os serviços
Comando: sudo systemctl start mariadb
Comando: sudo systemctl start apache2

C. Religar a Internet (Cloudflare)
Comando: nohup cloudflared tunnel --url http://localhost:80 > cloudflare.log 2>&1 &

[5] LIÇÕES APRENDIDAS

Documentar: Como entraram? Qual falha foi explorada?

Ação: Aplicar a correção para que não aconteça de novo.
