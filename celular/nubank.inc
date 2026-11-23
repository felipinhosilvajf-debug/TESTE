new PlayerText:NB_TD[MAX_PLAYERS][19];
new bool:NB_Aberto[MAX_PLAYERS];

stock CriarPlayerTD_Nubank(playerid)
{
    // 0: Fundo Escuro Principal
    NB_TD[playerid][0] = CreatePlayerTextDraw(playerid, 190.0, 115.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][0], 260.0, 225.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][0], 0x1A1A1AFF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][0], 4);

    // 1: Barra Superior Roxa Nubank
    NB_TD[playerid][1] = CreatePlayerTextDraw(playerid, 190.0, 115.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][1], 260.0, 32.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][1], 0x820AD1FF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][1], 4);

    // 2: Titulo NUBANK
    NB_TD[playerid][2] = CreatePlayerTextDraw(playerid, 200.0, 122.0, "NUBANK");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][2], 0.38, 1.60);
    PlayerTextDrawColor(playerid, NB_TD[playerid][2], -1);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][2], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][2], 2);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][2], 1);

    // 3: Subtitulo VIRTUAL BOOST
    NB_TD[playerid][3] = CreatePlayerTextDraw(playerid, 285.0, 124.0, "VIRTUAL BOOST");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][3], 0.26, 1.30);
    PlayerTextDrawColor(playerid, NB_TD[playerid][3], 0xE0B0FFFF);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][3], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][3], 2);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][3], 1);

    // 4: Botao X (Fechar) - Clicavel
    NB_TD[playerid][4] = CreatePlayerTextDraw(playerid, 432.0, 123.0, "X");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][4], 0.32, 1.40);
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][4], 20.0, 20.0);
    PlayerTextDrawAlignment(playerid, NB_TD[playerid][4], 2);
    PlayerTextDrawColor(playerid, NB_TD[playerid][4], -1);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][4], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][4], 2);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][4], 1);
    PlayerTextDrawSetSelectable(playerid, NB_TD[playerid][4], 1);

    // 5: Cartao Saldo em Maos
    NB_TD[playerid][5] = CreatePlayerTextDraw(playerid, 200.0, 158.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][5], 115.0, 48.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][5], 0x2A2A2AFF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][5], 4);

    // 6: Texto SALDO EM MAOS
    NB_TD[playerid][6] = CreatePlayerTextDraw(playerid, 206.0, 163.0, "SALDO EM MAOS");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][6], 0.18, 0.90);
    PlayerTextDrawColor(playerid, NB_TD[playerid][6], 0xCCCCCCFF);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][6], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][6], 1);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][6], 1);

    // 7: Valor Saldo em Maos
    NB_TD[playerid][7] = CreatePlayerTextDraw(playerid, 206.0, 178.0, "R$ 0");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][7], 0.28, 1.35);
    PlayerTextDrawColor(playerid, NB_TD[playerid][7], 0x2ECC71FF);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][7], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][7], 1);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][7], 1);

    // 8: Cartao Conta Nubank
    NB_TD[playerid][8] = CreatePlayerTextDraw(playerid, 325.0, 158.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][8], 115.0, 48.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][8], 0x2A2A2AFF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][8], 4);

    // 9: Texto CONTA NUBANK
    NB_TD[playerid][9] = CreatePlayerTextDraw(playerid, 331.0, 163.0, "CONTA NUBANK");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][9], 0.18, 0.90);
    PlayerTextDrawColor(playerid, NB_TD[playerid][9], 0xCCCCCCFF);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][9], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][9], 1);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][9], 1);

    // 10: Valor Conta Nubank
    NB_TD[playerid][10] = CreatePlayerTextDraw(playerid, 331.0, 178.0, "R$ 0");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][10], 0.28, 1.35);
    PlayerTextDrawColor(playerid, NB_TD[playerid][10], -1);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][10], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][10], 1);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][10], 1);

    // 11: Fundo Botao SACAR
    NB_TD[playerid][11] = CreatePlayerTextDraw(playerid, 200.0, 220.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][11], 74.0, 32.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][11], 0x820AD1FF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][11], 4);

    // 12: Texto Clicavel SACAR
    NB_TD[playerid][12] = CreatePlayerTextDraw(playerid, 237.0, 229.0, "SACAR");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][12], 0.24, 1.25);
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][12], 18.0, 74.0);
    PlayerTextDrawAlignment(playerid, NB_TD[playerid][12], 2);
    PlayerTextDrawColor(playerid, NB_TD[playerid][12], -1);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][12], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][12], 2);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][12], 1);
    PlayerTextDrawSetSelectable(playerid, NB_TD[playerid][12], 1);

    // 13: Fundo Botao DEPOSITAR
    NB_TD[playerid][13] = CreatePlayerTextDraw(playerid, 283.0, 220.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][13], 74.0, 32.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][13], 0x820AD1FF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][13], 4);

    // 14: Texto Clicavel DEPOSITAR
    NB_TD[playerid][14] = CreatePlayerTextDraw(playerid, 320.0, 229.0, "DEPOSITAR");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][14], 0.22, 1.25);
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][14], 18.0, 74.0);
    PlayerTextDrawAlignment(playerid, NB_TD[playerid][14], 2);
    PlayerTextDrawColor(playerid, NB_TD[playerid][14], -1);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][14], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][14], 2);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][14], 1);
    PlayerTextDrawSetSelectable(playerid, NB_TD[playerid][14], 1);

    // 15: Fundo Botao TRANSFERIR
    NB_TD[playerid][15] = CreatePlayerTextDraw(playerid, 366.0, 220.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][15], 74.0, 32.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][15], 0x820AD1FF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][15], 4);

    // 16: Texto Clicavel TRANSFERIR
    NB_TD[playerid][16] = CreatePlayerTextDraw(playerid, 403.0, 229.0, "TRANSFERIR");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][16], 0.21, 1.25);
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][16], 18.0, 74.0);
    PlayerTextDrawAlignment(playerid, NB_TD[playerid][16], 2);
    PlayerTextDrawColor(playerid, NB_TD[playerid][16], -1);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][16], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][16], 2);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][16], 1);
    PlayerTextDrawSetSelectable(playerid, NB_TD[playerid][16], 1);

    // 17: Fundo Botao ROUBAR BANCO
    NB_TD[playerid][17] = CreatePlayerTextDraw(playerid, 200.0, 288.0, "LD_SPAC:white");
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][17], 240.0, 32.0);
    PlayerTextDrawColor(playerid, NB_TD[playerid][17], 0xE74C3CFF);
    PlayerTextDrawFont(playerid, NB_TD[playerid][17], 4);

    // 18: Texto Clicavel ROUBAR BANCO
    NB_TD[playerid][18] = CreatePlayerTextDraw(playerid, 320.0, 297.0, "ROUBAR BANCO");
    PlayerTextDrawLetterSize(playerid, NB_TD[playerid][18], 0.26, 1.35);
    PlayerTextDrawTextSize(playerid, NB_TD[playerid][18], 18.0, 240.0);
    PlayerTextDrawAlignment(playerid, NB_TD[playerid][18], 2);
    PlayerTextDrawColor(playerid, NB_TD[playerid][18], -1);
    PlayerTextDrawSetShadow(playerid, NB_TD[playerid][18], 0);
    PlayerTextDrawFont(playerid, NB_TD[playerid][18], 2);
    PlayerTextDrawSetProportional(playerid, NB_TD[playerid][18], 1);
    PlayerTextDrawSetSelectable(playerid, NB_TD[playerid][18], 1);
    return 1;
}

stock AbrirNubank(playerid, mao, banco)
{
    new str[32];
    format(str, sizeof(str), "R$ %d", mao);
    PlayerTextDrawSetString(playerid, NB_TD[playerid][7], str);

    format(str, sizeof(str), "R$ %d", banco);
    PlayerTextDrawSetString(playerid, NB_TD[playerid][10], str);

    for(new i = 0; i < 19; i++)
    {
        PlayerTextDrawShow(playerid, NB_TD[playerid][i]);
    }

    NB_Aberto[playerid] = true;
    SelectTextDraw(playerid, 0x9B30FFFF);
    return 1;
}

stock FecharNubank(playerid)
{
    for(new i = 0; i < 19; i++)
    {
        PlayerTextDrawHide(playerid, NB_TD[playerid][i]);
    }

    NB_Aberto[playerid] = false;
    CancelSelectTextDraw(playerid);
    return 1;
}

stock CliquePlayerTD_Nubank(playerid, PlayerText:playertextid)
{
    if(!NB_Aberto[playerid]) return 0;

    if(playertextid == NB_TD[playerid][4]) // Botao X
    {
        FecharNubank(playerid);
        return 1;
    }
    if(playertextid == NB_TD[playerid][14]) // DEPOSITAR
    {
        FecharNubank(playerid);
        new str[128];
        format(str, sizeof(str), "Seu Saldo atual e : R$[%d]\nColoque a quantidade para depositar .", PlayerInfo[playerid][pConta]);
        ShowPlayerDialog(playerid, 64, DIALOG_STYLE_INPUT, "{820AD1}Nubank {ffffff}- Deposito", str, "Depositar", "Voltar");
        return 1;
    }
    if(playertextid == NB_TD[playerid][12]) // SACAR
    {
        FecharNubank(playerid);
        new str[128];
        format(str, sizeof(str), "Seu Saldo atual e: R$[%d]\nColoque a quantidade para retirar .", PlayerInfo[playerid][pConta]);
        ShowPlayerDialog(playerid, 65, DIALOG_STYLE_INPUT, "{820AD1}Nubank {ffffff}- Saque", str, "Sacar", "Voltar");
        return 1;
    }
    if(playertextid == NB_TD[playerid][16]) // TRANSFERIR
    {
        FecharNubank(playerid);
        new str[128];
        format(str, sizeof(str), "Seu Saldo atual e: R$[%d]\nColoque o ID do Jogador para transferir .", PlayerInfo[playerid][pConta]);
        ShowPlayerDialog(playerid, 67, DIALOG_STYLE_INPUT, "{820AD1}Nubank {ffffff}- Transferencia", str, "Proximo", "Voltar");
        return 1;
    }
    if(playertextid == NB_TD[playerid][18]) // ROUBAR BANCO
    {
        FecharNubank(playerid);
        ForT:roubarcaixa(playerid, #);
        return 1;
    }
    return 0;
}
