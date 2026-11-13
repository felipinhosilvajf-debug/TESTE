# Virtual Boost - Wipe controlado dos dados de scriptfiles
# Execute este arquivo na pasta raiz do servidor (a pasta que contem scriptfiles).
# O script cria um backup completo antes de alterar qualquer arquivo.

$ErrorActionPreference = "Stop"
$DataRoot = Join-Path $PSScriptRoot "scriptfiles"

if (-not (Test-Path -LiteralPath $DataRoot -PathType Container)) {
    throw "Pasta scriptfiles nao encontrada. Coloque este .ps1 na raiz do servidor."
}

$Stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupRoot = Join-Path $PSScriptRoot ("scriptfiles_backup_" + $Stamp)
Copy-Item -LiteralPath $DataRoot -Destination $BackupRoot -Recurse
Write-Host "Backup criado em: $BackupRoot" -ForegroundColor Green

# Os arquivos DOF2 deste GameMode usam arquivos texto ANSI/Windows-1252.
$Encoding = [System.Text.Encoding]::GetEncoding(1252)
$Changed = 0

function Set-IniValue {
    param(
        [string]$Text,
        [string]$Key,
        [string]$Value
    )

    $EscapedKey = [regex]::Escape($Key)
    # Aceita tanto o separador "=" quanto o separador legado 0x9C encontrado
    # em alguns arquivos antigos de Cofre Org.
    $Pattern = "(?im)^(\s*" + $EscapedKey + "\s*(?:=|\x9C)\s*).*$"
    return [regex]::Replace($Text, $Pattern, ('$1' + $Value))
}

function Update-FileKeys {
    param(
        [string]$Path,
        [hashtable]$Values
    )

    $Text = [System.IO.File]::ReadAllText($Path, $Encoding)
    $Original = $Text
    foreach ($Key in $Values.Keys) {
        $Text = Set-IniValue -Text $Text -Key $Key -Value ([string]$Values[$Key])
    }
    if ($Text -cne $Original) {
        [System.IO.File]::WriteAllText($Path, $Text, $Encoding)
        $script:Changed++
    }
}

function Get-Files {
    param([string]$RelativePath, [string]$Filter = "*")
    $FullPath = Join-Path $DataRoot $RelativePath
    if (Test-Path -LiteralPath $FullPath -PathType Container) {
        return Get-ChildItem -LiteralPath $FullPath -File -Filter $Filter -Recurse
    }
    return @()
}

# 1) Organizações: remover lideres, sublideres e membros sem apagar os cadastros.
$MembershipValues = @{ Lider = "Ninguem"; SubLider = "Ninguem"; "SubLíder" = "Ninguem" }
for ($i = 0; $i -le 40; $i++) { $MembershipValues["Membro$i"] = "Ninguem" }

foreach ($Folder in @("InfoOrg", "ORG Arquivos", "OrgsSV")) {
    foreach ($File in (Get-Files $Folder)) {
        Update-FileKeys -Path $File.FullName -Values $MembershipValues
    }
}

# 2) Candidaturas antigas: deixar todos os slots vazios.
$CandidateValues = @{}
for ($i = 1; $i -le 20; $i++) { $CandidateValues["Candidato$i"] = "Ninguem" }
foreach ($File in (Get-Files "Candidatos")) {
    Update-FileKeys -Path $File.FullName -Values $CandidateValues
}

# 3) Familias: manter os 20 cadastros, mas sem nome, lideranca ou membros.
$FamilyValues = @{
    NomeFamilia = "Ninguem"
    Lider = "Ninguem"
    SubLider = "Ninguem"
    "SubLíder" = "Ninguem"
    Dias = "0"
    Data = "0"
}
for ($i = 1; $i -le 40; $i++) { $FamilyValues["Membro$i"] = "Ninguem" }
foreach ($File in (Get-Files "Familias" "Familia*.cfg")) {
    Update-FileKeys -Path $File.FullName -Values $FamilyValues
}

# 4) Organizações VIP: retirar o membro e zerar a validade vinculada.
$OrgVipValues = @{ Tipo = "0"; Dias = "0"; Data = "0"; Membro0 = "Ninguem" }
foreach ($File in (Get-Files "ORG VIPS" "*.ini")) {
    Update-FileKeys -Path $File.FullName -Values $OrgVipValues
}

# 4b) Votacoes de familias: zerar os totais anteriores.
foreach ($File in (Get-Files "Votos Familia" "*.ini")) {
    Update-FileKeys -Path $File.FullName -Values @{ "Total votos" = "0" }
}

# 5) Cofres das organizações (inclui backups e subpastas).
# Mantém salários, preços, limites e configurações; zera somente saldo/estoque,
# contadores de transações e datas/validade vinculadas ao antigo líder/VIP.
$VaultValues = @{
    Dinheiro = "0"
    Materiais = "0"
    Maconha = "0"
    Cocaina = "0"
    Crack = "0"
    DarMaconha = "0"
    ComMaconha = "0"
    ComMateriais = "0"
    PagMaconha = "0"
    PagMateriais = "0"
    MatBox1 = "0"
    MatBox2 = "0"
    MatBox3 = "0"
    Vip = "0"
    Diasvip = "0"
    DiaVIP = "0"
    MesVIP = "0"
    AnoVIP = "0"
    DiaLider = "0"
    "DiaLíder" = "0"
}
foreach ($File in (Get-Files "Cofre Org" "*.cfg")) {
    Update-FileKeys -Path $File.FullName -Values $VaultValues
}

# 6) Cofres gerais do governo, mecanica e SAMU.
foreach ($File in (Get-Files "Configs" "Cofre*.cfg")) {
    Update-FileKeys -Path $File.FullName -Values @{ Dinheiro = "0" }
}

# 7) Casas: preservar coordenadas, interiores, mobília, valores e configurações.
#    Somente remover proprietário e renda acumulada.
foreach ($File in (Get-Files "Props/Casas" "*.ini")) {
    Update-FileKeys -Path $File.FullName -Values @{
        Dono = "O estado"
        TemDono = "0"
        Takings = "0"
    }
}

# 8) Empresas, miniempresas e fazendas: preservar cadastro/localização/preço,
#    mas devolver ao Estado, zerar caixa e estoque.
foreach ($Folder in @("Props/Empresas", "Props/MiniEmpresas", "Props/Fazendas")) {
    foreach ($File in (Get-Files $Folder "*.ini")) {
        Update-FileKeys -Path $File.FullName -Values @{
            Dono = "O estado"
            TemDono = "0"
            Extorcao = "Ninguem"
            Till = "0"
            Produtos = "0"
        }
    }
}

# 9) Arquivos-resumo usados pelo sistema de empresas (linhas separadas por |).
# Bizz: TemDono|Dono|Descricao|Extorcao|...|CustoEntrada|Till|Locked|Interior
$BizzPath = Join-Path $DataRoot "Configs/Bizz.cfg"
if (Test-Path -LiteralPath $BizzPath) {
    $Lines = [System.IO.File]::ReadAllLines($BizzPath, $Encoding)
    $NewLines = foreach ($Line in $Lines) {
        if ([string]::IsNullOrWhiteSpace($Line)) { $Line; continue }
        $Parts = $Line -split "\|"
        if ($Parts.Count -ge 16) {
            $Parts[0] = "0"
            $Parts[1] = "O estado"
            $Parts[13] = "0"
            ($Parts -join "|")
        } else { $Line }
    }
    [System.IO.File]::WriteAllLines($BizzPath, [string[]]$NewLines, $Encoding)
    $script:Changed++
}

# Sbizz: TemDono|Dono|Descricao|Extorcao|...|CustoEntrada|Till|Locked
$SbizzPath = Join-Path $DataRoot "Configs/Sbizz.cfg"
if (Test-Path -LiteralPath $SbizzPath) {
    $Lines = [System.IO.File]::ReadAllLines($SbizzPath, $Encoding)
    $NewLines = foreach ($Line in $Lines) {
        if ([string]::IsNullOrWhiteSpace($Line)) { $Line; continue }
        $Parts = $Line -split "\|"
        if ($Parts.Count -ge 12) {
            $Parts[0] = "0"
            $Parts[1] = "O estado"
            $Parts[10] = "0"
            ($Parts -join "|")
        } else { $Line }
    }
    [System.IO.File]::WriteAllLines($SbizzPath, [string[]]$NewLines, $Encoding)
    $script:Changed++
}

Write-Host ""
Write-Host "WIPE CONCLUIDO." -ForegroundColor Green
Write-Host "Arquivos atualizados: $Changed"
Write-Host "Backup preservado em: $BackupRoot"
Write-Host "Contas de jogadores, mapa, coordenadas, precos, salarios e configuracoes foram preservados."
Write-Host "Reinicie o servidor somente depois de conferir o backup e os arquivos."
