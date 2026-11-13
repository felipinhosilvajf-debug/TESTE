
new Text:PayDayText[1];

new Text:ContagemPayday[1];

	PayDayText[0] = TextDrawCreate(521.000000, 240.000000, "PAYDAY");
	TextDrawFont(PayDayText[0], 3);
	TextDrawLetterSize(PayDayText[0], 0.370833, 1.350000);
	TextDrawTextSize(PayDayText[0], 400.000000, 17.000000);
	TextDrawSetOutline(PayDayText[0], 2);
	TextDrawSetShadow(PayDayText[0], 0);
	TextDrawAlignment(PayDayText[0], 1);
	TextDrawColor(PayDayText[0], -1);
	TextDrawBackgroundColor(PayDayText[0], 65535);
	TextDrawBoxColor(PayDayText[0], 50);
	TextDrawUseBox(PayDayText[0], 0);
	TextDrawSetProportional(PayDayText[0], 1);
	TextDrawSetSelectable(PayDayText[0], 0);

	ContagemPayday[0] = TextDrawCreate(577.000000, 240.000000, "59:58");
	TextDrawFont(ContagemPayday[0], 3);
	TextDrawLetterSize(ContagemPayday[0], 0.370833, 1.350000);
	TextDrawTextSize(ContagemPayday[0], 400.000000, 17.000000);
	TextDrawSetOutline(ContagemPayday[0], 2);
	TextDrawSetShadow(ContagemPayday[0], 0);
	TextDrawAlignment(ContagemPayday[0], 1);
	TextDrawColor(ContagemPayday[0], -1);
	TextDrawBackgroundColor(ContagemPayday[0], 65535);
	TextDrawBoxColor(ContagemPayday[0], 50);
	TextDrawUseBox(ContagemPayday[0], 0);
	TextDrawSetProportional(ContagemPayday[0], 1);
	TextDrawSetSelectable(ContagemPayday[0], 0);


	//Global TextDraws: 


new Text:PayDayText[1];

PayDayText[0] = TextDrawCreate(530.800170, 253.715530, "payday");
TextDrawLetterSize(PayDayText[0], 0.369599, 1.361067);
TextDrawAlignment(PayDayText[0], 1);
TextDrawColor(PayDayText[0], -1);
TextDrawSetShadow(PayDayText[0], 0);
TextDrawSetOutline(PayDayText[0], -1);
TextDrawBackgroundColor(PayDayText[0], 65535);
TextDrawFont(PayDayText[0], 3);
TextDrawSetProportional(PayDayText[0], 1);



//Player TextDraws: 


new PlayerText:ContagemPayday[MAX_PLAYERS][1];

ContagemPayday[playerid][0] = CreatePlayerTextDraw(playerid, 578.000122, 254.213394, "59:58");
PlayerTextDrawLetterSize(playerid, ContagemPayday[playerid][0], 0.368399, 1.316266);
PlayerTextDrawAlignment(playerid, ContagemPayday[playerid][0], 1);
PlayerTextDrawColor(playerid, ContagemPayday[playerid][0], -1);
PlayerTextDrawSetShadow(playerid, ContagemPayday[playerid][0], 0);
PlayerTextDrawSetOutline(playerid, ContagemPayday[playerid][0], -1);
PlayerTextDrawBackgroundColor(playerid, ContagemPayday[playerid][0], 65535);
PlayerTextDrawFont(playerid, ContagemPayday[playerid][0], 3);
PlayerTextDrawSetProportional(playerid, ContagemPayday[playerid][0], 1);




//Total textdraws exported: 2 (1 global textdraws / 1 player textdraws) ~ 16/8/2021 ~ 14:12:26
TDEditor V1.18 BY ADRI1