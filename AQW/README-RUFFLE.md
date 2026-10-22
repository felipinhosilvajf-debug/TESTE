# MeuAQWClient — integração Ruffle

Este branch prepara a estrutura Electron para carregar o cliente Flash via Ruffle e inclui um proxy WebSocket→TCP limitado ao host/portas configurados pelo administrador.

## Antes de executar

1. Descubra o URL exato do arquivo SWF do seu próprio cliente AQW. Não presuma que seja `/game/gamefiles/Loader_Spider.swf`; confirme no site/servidor.
2. Confirme o hostname que o SWF usa em `Socket.connect()` e a(s) porta(s) TCP que o SmartFoxServer escuta.
3. Preencha `config.json` com esses valores. `tcpTargetHost` é o IP/DNS de destino visto pelo computador do jogador; ele já está definido para o IP usado em `main.js` antes desta alteração.
4. No terminal, dentro da pasta `AQW`, execute:
   ```powershell
   npm install
   npm start
   ```
5. Para gerar o instalador Windows:
   ```powershell
   npm run build
   ```
   O instalador ficará em `AQW/dist`.

## Configuração

- `gameSwfUrl`: URL completa do SWF do cliente do seu servidor.
- `socketHost`: hostname exato que o SWF tenta acessar; não é necessariamente o IP do servidor.
- `tcpTargetHost`: IP/DNS para onde o proxy TCP se conecta.
- `socketPorts`: portas TCP que precisam ser encaminhadas. Inclua apenas portas confirmadas pelo seu emulador/configuração.
- `proxyPort`: porta local do WebSocket; fica vinculada a `127.0.0.1`.

A configuração fica embutida no pacote que você distribui; o jogador não precisa digitar IP ou porta.

## Limitação importante

Isto ainda não é um build validado do jogo. A conexão só vai funcionar depois de confirmar o SWF real, o hostname e as portas do SmartFoxServer. O Ruffle também pode não suportar todas as APIs ActionScript usadas pelo cliente AQW. Teste login e conexão antes de distribuir.
