# Virtual Boost Launcher (Windows)

Primeira versão do launcher próprio do Virtual Boost SAMP RPG.

## O que já está implementado

- Interface Windows Forms com identidade escura e verde do Virtual Boost.
- IP e porta fixos: `163.245.202.163:7777`.
- Campo de nick com validação básica.
- Detecção do GTA San Andreas por caminhos comuns e registro do Windows.
- Detecção do open.mp e do SA-MP.
- Botão para baixar e abrir o instalador oficial do open.mp diretamente pelo launcher.
- Download com barra de progresso e validação SHA-256 antes de executar o instalador.
- Preferência automática pelo open.mp quando os dois clientes são encontrados.
- Inicialização do cliente com os parâmetros de conexão.
- Seleção manual da pasta do GTA quando a detecção automática não encontrar a instalação.

## Requisitos para compilar

- Windows 10 ou 11.
- .NET 8 SDK (Windows).
- GTA San Andreas para PC instalado.
- Conexão com a internet para baixar o instalador oficial do open.mp, caso necessário.

O jogador continua precisando ter uma cópia legítima do GTA San Andreas instalada. O launcher não distribui o GTA. O cliente open.mp é baixado do repositório oficial do projeto; o assistente oficial de instalação é executado pelo Windows. O open.mp também oferece seleção/instalação de versões de cliente SA-MP.

## Compilar no Windows

Abra o PowerShell nesta pasta e execute:

```powershell
dotnet restore
dotnet build -c Release
```

Para gerar um executável Windows x64 independente:

```powershell
dotnet publish -c Release -r win-x64 --self-contained true /p:PublishSingleFile=true
```

O executável publicado ficará em:

`bin\Release\net8.0-windows\win-x64\publish\VirtualBoostLauncher.exe`

## Observações importantes

- O open.mp é iniciado com `-h`, `-p`, `-n` e `-g`, conforme os parâmetros de linha de comando do cliente.
- O SA-MP recebe o endereço `IP:PORTA` como primeiro argumento, seguido do nick.
- A detecção automática usa caminhos comuns e entradas de registro. Instalações portáteis ou personalizadas podem precisar da seleção manual da pasta do GTA.
- Esta é uma primeira base de desenvolvimento. A compilação e o teste real com as instalações dos jogadores ainda precisam ser feitos antes de distribuir o instalador.

## Próximas etapas

1. Testar em uma máquina com SA-MP instalado.
2. Testar em outra máquina com open.mp instalado.
3. Melhorar a detecção de instalações personalizadas com base nos caminhos reais usados pela comunidade.
4. Testar a instalação do open.mp em um Windows limpo e confirmar a detecção após concluir o assistente.
5. Testar o fluxo de instalação/seleção do cliente SA-MP dentro do open.mp.
6. Adicionar verificação de atualização, logs e instalador do Virtual Boost.
