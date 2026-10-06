class WarMemberWarning extends UICommonAPI;

const ID_OFFSET = 15000000;
const ID_ITERACTION_OFFSET = 65000;

var WindowHandle h_Timer;
var WindowHandle h_Option;

var ListCtrlHandle WarFromMyClan;
var ListCtrlHandle WarToMyClan;

var ListCtrlHandle Me;

var texture ClanTexObj;
var texture AllianceTexObj;

var TextureHandle ClanCrest;
var TextureHandle AllianceCrest;

var TextBoxHandle t_War;
var TextBoxHandle t_All;

var EditBoxHandle e_name[9];

var AnimTextureHandle a_radar;

var ButtonHandle b_scan;

var ProgressCtrlHandle p_scan;

var LVDataRecord mainRec;	

var array<string> PlayerNames;
var array<string> WarClanNames;

var int counterWar;
var int counterAll;

var Color cPK;

var PartyWnd script_pt;

var int _xdd;
var int range1s;
var int range2s;

var bool isScanning;


function OnRegisterEvent()
{
	RegisterEvent(EV_ReceiveMagicSkillUse);
	RegisterEvent(EV_ClanWarList);
}

function OnEvent (int EventID, string param)
{
	switch (EventID)
	{
		case EV_ReceiveMagicSkillUse:
			if (h_Timer.IsShowWindow() && GetUIState() == "GAMINGSTATE")
				CheckPlayer(param);
		break;
	}
}

function OnLoad()
{
	local int i;
	
	WarFromMyClan = GetListCtrlHandle("ClanDrawerWnd.Clan8_DeclaredListCtrl");
	WarToMyClan = GetListCtrlHandle("ClanDrawerWnd.Clan8_GotDeclaredListCtrl");
	
	t_War = GetTextBoxHandle("WarMemberWarning.counterWar");
	t_All = GetTextBoxHandle("WarMemberWarning.counterAll");
	
	Me = GetListCtrlHandle("WarMemberWarning.MainList");
	
	h_Timer = GetWindowHandle("WarMemberWarning");
	
	h_Option = GetWindowHandle("WarMemberWarning.Options");
	
	a_radar = GetAnimTextureHandle("WarMemberWarning.texScanAnim");
	a_radar.HideWindow();
	
	b_scan = GetButtonHandle("WarMemberWarning.btnScan");
	
	p_scan = GetProgressCtrlHandle("WarMemberWarning.progressScan");
	p_scan.HideWindow();
	
	for (i = 0; i < 9; i++)
		e_name[i] = GetEditBoxHandle("WarMemberWarning.Options.name"$ string(i));
	
	script_pt = PartyWnd(GetScript("PartyWnd"));
	
	PlayerNames.Length = 1;
	WarClanNames.Length = 1;
	
	counterAll = 0;
	counterWar = 0;
	
	cPK.R = 201; cPK.G = 0; cPK.B = 0;
	b_scan.SetTooltipCustomType(MakeTooltipSimpleText("Scanning reduces your FPS!"));
}
function AnimStart(bool bFlag)
{
	if (bFlag)
	{
		a_radar.ShowWindow();
		a_radar.Stop();
		a_radar.SetLoopCount(-1);
		a_radar.Play();
		p_scan.ShowWindow();
		p_scan.SetProgressTime(26000);
		p_scan.SetPos(26000);
		p_scan.Reset();
		p_scan.Start();
		//h_Timer.SetAlpha(125, 0.f);
		//h_Option.SetAlpha(125, 0.f);
	}
	else
	{
		a_radar.HideWindow();
		a_radar.Stop();
		p_scan.HideWindow();
		//h_Timer.SetAlpha(255, 0.f);
		//h_Option.SetAlpha(255, 0.f);
	}
}

function OnClickButton(string strID)
{
	//local int i;
	
	switch(strID)
	{
		case "btnOption":
			if (h_Option.IsShowWindow())
				h_Option.HideWindow();
			else
				h_Option.ShowWindow();
		break;
		case "btnClose":
			h_Timer.HideWindow();
		break;
		case "btnScan":
			if (!isScanning)
			{
				OnClickScan();
				AnimStart(true);
				isScanning = true;
				//sysDebug("START SCANNING");
			}
			else
			{
				h_Timer.KillTimer(3340);
				AnimStart(false);
				isScanning = false;
				//sysDebug("STOP SCANNING");
			}
		break;
	}
}

function _XD(UserInfo pInfo, UserInfo tryInfo)
{
	local int i, j;
	
	local bool isWarClan;
	local bool isPvPMode;
	local bool isPKMode;
	local bool isFromList;
	
	local string clanName;
	
	local LVDataRecord CheckRec;
	
	isWarClan = false;
			isPvPMode = false;
			isPKMode = false;
			isFromList = false;
			
			//Check if clan member
			if (pInfo.nClanID > 0 && tryInfo.nClanID == pInfo.nClanID)
			{
				return;
			}
			
			//Check if party member
			if (GetPartyMemberCount() > 0)
			{
				for (i = 0; i < GetPartyMemberCount(); i++)
				{
					if (tryInfo.nID == script_pt.m_arrID[i])
					{
						return;
					}
				}		
			}
			
			isWarClan = isEnemyClan(tryInfo, pInfo);
	
			if (tryInfo.nID != pInfo.nID && !tryInfo.bNpc && !tryInfo.bPet && tryInfo.bCanBeAttacked )
			{
				isPvPMode = true;
			}
			
			if (tryInfo.nID != pInfo.nID && !tryInfo.bNpc && !tryInfo.bPet && tryInfo.nCriminalRate > 0 )
			{
				isPKMode = true;
			}
				
			
			for (i = 0; i < 9; i++)
			{
				if (e_name[i].GetString() != "" && e_name[i].GetString() == tryInfo.Name)
				{
					isFromList = true;
				}
			}
			
			if ( isWarClan || isPvPMode || isPKMode || isFromList )
			{
				
				for (i = 0; i < PlayerNames.Length; i++)
				{
					if (PlayerNames[i] == tryInfo.Name)
					{
						for (j = 0; j < Me.GetRecordCount(); j++)
						{
							Me.GetRec(j, CheckRec);
							if(CheckRec.LVDataList[2].szData == tryInfo.Name) 
							{
								return;
							}
						}
						PlayerNames.Remove(i, 1);
					}
				}
				
				PlayerNames[ PlayerNames.Length - 1 ] = tryInfo.Name;

				clanName = class'UIDATA_CLAN'.static.GetName(tryInfo.nClanID);
				
				if (isWarClan)
				{
					counterWar += 1;
				}
					
				mainRec.LVDataList.Length = 5;
				mainRec.LVDataList[0].arrTexture.Length = 2;
				
				if (class'UIDATA_CLAN'.static.GetCrestTexture(tryInfo.nClanID, ClanTexObj))
				{
					mainRec.LVDataList[0].arrTexture[0].objTex = ClanTexObj;
					mainRec.LVDataList[0].arrTexture[0].X = 12;
					mainRec.LVDataList[0].arrTexture[0].Y = 0;
					mainRec.LVDataList[0].arrTexture[0].Width = 16;
					mainRec.LVDataList[0].arrTexture[0].Height = 12;
					mainRec.LVDataList[0].arrTexture[0].U = 0;
					mainRec.LVDataList[0].arrTexture[0].V = 4;
					mainRec.LVDataList[0].arrTexture[0].UL = 16;
					mainRec.LVDataList[0].arrTexture[0].VL = 12;
				}
				else
				{
					mainRec.LVDataList[0].arrTexture[0].objTex = ClanTexObj;
					mainRec.LVDataList[0].arrTexture[0].X = 0;
					mainRec.LVDataList[0].arrTexture[0].Y = 0;
					mainRec.LVDataList[0].arrTexture[0].Width = 0;
					mainRec.LVDataList[0].arrTexture[0].Height = 0;
					mainRec.LVDataList[0].arrTexture[0].U = 0;
					mainRec.LVDataList[0].arrTexture[0].V = 0;
					mainRec.LVDataList[0].arrTexture[0].UL = 0;
					mainRec.LVDataList[0].arrTexture[0].VL = 0;
					mainRec.LVDataList[0].nReserved1 = 0;
				}
				
				if (class'UIDATA_CLAN'.static.GetAllianceCrestTexture(tryInfo.nClanID, AllianceTexObj))
				{
					mainRec.LVDataList[0].arrTexture[1].objTex = AllianceTexObj;
					mainRec.LVDataList[0].arrTexture[1].X = 4;
					mainRec.LVDataList[0].arrTexture[1].Y = 0;
					mainRec.LVDataList[0].arrTexture[1].Width = 8;
					mainRec.LVDataList[0].arrTexture[1].Height = 12;
					mainRec.LVDataList[0].arrTexture[1].U = 0;
					mainRec.LVDataList[0].arrTexture[1].V = 4;
					mainRec.LVDataList[0].arrTexture[1].UL = 8;
					mainRec.LVDataList[0].arrTexture[1].VL = 12;
				}
				else
				{
					mainRec.LVDataList[0].arrTexture[1].objTex = AllianceTexObj;
					mainRec.LVDataList[0].arrTexture[1].X = 0;
					mainRec.LVDataList[0].arrTexture[1].Y = 0;
					mainRec.LVDataList[0].arrTexture[1].Width = 0;
					mainRec.LVDataList[0].arrTexture[1].Height = 0;
					mainRec.LVDataList[0].arrTexture[1].U = 0;
					mainRec.LVDataList[0].arrTexture[1].V = 0;
					mainRec.LVDataList[0].arrTexture[1].UL = 0;
					mainRec.LVDataList[0].arrTexture[1].VL = 0;
				}
				
				mainRec.LVDataList[0].nReserved1 = tryInfo.nID;
				mainRec.LVDataList[0].szData = class'UIDATA_CLAN'.static.GetName(tryInfo.nClanID);
				
				mainRec.LVDataList[2].szData = tryInfo.Name;
				if (isPvPMode)
				{
					mainRec.LVDataList[1].szData = "PvP";
				}
				if (isPKMode)
				{
					mainRec.LVDataList[1].szData = "PK";
				}
				if (!isPvPMode && !isPKMode)
				{
					mainRec.LVDataList[1].szData = "N";
				}
				mainRec.LVDataList[2].nReserved1 = tryInfo.Loc.X;
				mainRec.LVDataList[2].nReserved2 = tryInfo.Loc.Y;
				mainRec.LVDataList[2].nReserved3 = tryInfo.Loc.Z;
				
				mainRec.LVDataList[3].szData = string(tryInfo.nSubClass);
				mainRec.LVDataList[3].szTexture = GetClassIconName(tryInfo.nSubClass);
				mainRec.LVDataList[3].nTextureWidth = 11;
				mainRec.LVDataList[3].nTextureHeight = 11;
					
				mainRec.LVDataList[4].szData = string(GetDistance( pInfo.Loc, tryInfo.Loc ));
					
				class'UIAPI_LISTCTRL'.static.InsertRecord( "WarMemberWarning.MainList", mainRec );
				
				for (i = 0; i < Me.GetRecordCount(); i++)
				{
					PlayerNames.Length = Me.GetRecordCount();
					Me.GetRec( i, CheckRec);
					PlayerNames[i] = CheckRec.LVDataList[2].szData;
				}
			}
}

function ParseIDs(int range1, int range2)
{
	local int idx;
	
	local UserInfo tryInfo;
	local UserInfo pInfo;
	
	GetPlayerInfo( pInfo );
	
	for (idx = range1; idx < range2; idx++)
	{
		if (class'UIDATA_USER'.static.GetUserName(idx) != "")
		{
			if ( GetUserInfo(idx, tryInfo) )
			{
				_XD(pInfo, tryInfo);
			}
		}
	}

	//sysDebug("Calculations completed!");
}

function OnClickScan()
{
	_xdd = 0;
	range1s = class'UIDATA_PLAYER'.static.GetPlayerID() - ID_OFFSET;
	range2s = class'UIDATA_PLAYER'.static.GetPlayerID() - ID_OFFSET + ID_ITERACTION_OFFSET;
	ParseIDs(range1s, range2s);
	_xdd += 1;
	
	h_Timer.SetTimer(3340, 50);
}

function OnShow()
{
	local UserInfo pInfo;
	
	GetPlayerInfo( pInfo );
	
	Me.DeleteAllItem();
	h_Timer.KillTimer(3328);
	h_Timer.SetTimer(3328, 1000);
	
	if ( pInfo.nClanID > 0 )
	{
		//state 0
		RequestClanWarList(-1, 0);
		//RequestClanWarList(0, 0);
		//RequestClanWarList(1, 0);
		//state 1
		RequestClanWarList(-1, 1);
		//RequestClanWarList(0, 1);
		//RequestClanWarList(1, 1);
	}
}

function OnHide()
{
	h_Timer.KillTimer(3328);
	Me.DeleteAllItem();
	h_Timer.KillTimer(3340);
	t_War.SetText( "0" );
	t_All.SetText( "0" );
	AnimStart(false);
	isScanning = false;
}

function bool NameFromChosen(string name)
{
	local int i;
	
	for (i = 0; i < 9; i++)
		if (e_name[i].GetString() == name)
			return true;
		
	return false;
}

function OnTimer(int TimerID)
{
	local int recCount;
	local int i;
	local int j;
	local LVDataRecord record;
	local LVDataRecord newrecord;
	local UserInfo user, enemy;
	
	if (TimerID == 3340)
	{
		h_Timer.KillTimer(3340);
		range1s = range2s;
		range2s = range2s + ID_ITERACTION_OFFSET;
		if (range2s < class'UIDATA_PLAYER'.static.GetPlayerID() + ID_OFFSET)
		{
			ParseIDs(range1s, range2s);
			h_Timer.SetTimer(3340, 50);
			_xdd += 1;
			
		}
		else
		{
			AnimStart(false);
			isScanning = false;
			//sysDebug("AHH SHIEET - " $ string(_xdd));
		}
	}
	
	if (TimerID == 3328)
	{
		h_Timer.KillTimer(3328);
		recCount = Me.GetRecordCount();
		if (recCount == 0)
		{
			counterAll = 0;
			counterWar = 0;
			for(i = 0; i < PlayerNames.Length; i++)
				PlayerNames[i] = "";
		}
		
		for (i = 0; i < recCount; i++)
		{
			Me.GetRec(i, record);
			GetPlayerInfo(user);
			if (!GetUserInfo(record.LVDataList[0].nReserved1, enemy))
			{
				for (j = 0; j < recCount; j++)
					if (record.LVDataList[2].szData == PlayerNames[j])
					{
						PlayerNames.Remove(j, 1);
					}
					
				for (j = 0; j < WarClanNames.Length; j++)
				{
					if (record.LVDataList[0].szData == WarClanNames[j])
					{
						counterWar -= 1;
					}
				}
				
				
				Me.DeleteRecord(i);
		
				recCount = Me.GetRecordCount();
				//sysDebug("RECORD DELETED - NOT GETTING INFO");
				i -= 1;
				continue;
			}
			
			if (GetDistance( user.Loc, enemy.Loc ) > 4000)
			{
				for (j = 0; j < recCount; j++)
					if (record.LVDataList[2].szData == PlayerNames[j])
					{
						PlayerNames.Remove(j, 1);
					}
					
				for (j = 0; j < WarClanNames.Length; j++)
				{
					if (record.LVDataList[0].szData == WarClanNames[j])
					{
						counterWar -= 1;
					}
				}
				
				Me.DeleteRecord(i);

				//sysDebug("RECORD DELETED - DISTANCE");
				recCount = Me.GetRecordCount();
				i -= 1;
				continue;
			}
			
			if ( !isEnemyClan(enemy, user) && !enemy.bCanBeAttacked && enemy.nCriminalRate == 0 && !NameFromChosen(enemy.Name))
			{
				for (j = 0; j < recCount; j++)
					if (record.LVDataList[2].szData == PlayerNames[j])
					{
						PlayerNames.Remove(j, 1);
					}
				
				Me.DeleteRecord(i);
				recCount = Me.GetRecordCount();
				i -= 1;
				continue;
			}
		}

		recCount = Me.GetRecordCount();
		counterAll = recCount;
			
		for (i = 0; i < recCount; i++)
		{
			GetPlayerInfo(user);
			Me.GetRec(i, record);
			GetUserInfo(record.LVDataList[0].nReserved1, enemy);
			
			Me.InitListCtrl();
			
			newrecord.LVDataList.Length = 5;
			newrecord.LVDataList[0].arrTexture.Length = 2;
			
			newrecord.LVDataList[0].arrTexture[0].objTex = record.LVDataList[0].arrTexture[0].objTex;
			newrecord.LVDataList[0].arrTexture[0].X = record.LVDataList[0].arrTexture[0].X;
			newrecord.LVDataList[0].arrTexture[0].Y = record.LVDataList[0].arrTexture[0].Y;
			newrecord.LVDataList[0].arrTexture[0].Width = record.LVDataList[0].arrTexture[0].Width;
			newrecord.LVDataList[0].arrTexture[0].Height = record.LVDataList[0].arrTexture[0].Height;
			newrecord.LVDataList[0].arrTexture[0].U = record.LVDataList[0].arrTexture[0].U;
			newrecord.LVDataList[0].arrTexture[0].V = record.LVDataList[0].arrTexture[0].V;
			newrecord.LVDataList[0].arrTexture[0].UL = record.LVDataList[0].arrTexture[0].UL;
			newrecord.LVDataList[0].arrTexture[0].VL = record.LVDataList[0].arrTexture[0].VL;
				
			newrecord.LVDataList[0].arrTexture[1].objTex = record.LVDataList[0].arrTexture[1].objTex;
			newrecord.LVDataList[0].arrTexture[1].X = record.LVDataList[0].arrTexture[1].X;
			newrecord.LVDataList[0].arrTexture[1].Y = record.LVDataList[0].arrTexture[1].Y;
			newrecord.LVDataList[0].arrTexture[1].Width = record.LVDataList[0].arrTexture[1].Width;
			newrecord.LVDataList[0].arrTexture[1].Height = record.LVDataList[0].arrTexture[1].Height;
			newrecord.LVDataList[0].arrTexture[1].U = record.LVDataList[0].arrTexture[1].U;
			newrecord.LVDataList[0].arrTexture[1].V = record.LVDataList[0].arrTexture[1].V;
			newrecord.LVDataList[0].arrTexture[1].UL = record.LVDataList[0].arrTexture[1].UL;
			newrecord.LVDataList[0].arrTexture[1].VL = record.LVDataList[0].arrTexture[1].VL;
			
			newrecord.LVDataList[0].nReserved1 = record.LVDataList[0].nReserved1;
			newrecord.LVDataList[0].szData = record.LVDataList[0].szData;
			
			newrecord.LVDataList[1].szData = "N";
			
			newrecord.LVDataList[2].szData = record.LVDataList[2].szData;
			newrecord.LVDataList[2].bUseTextColor = true;
			newrecord.LVDataList[2].TextColor = cPK;
			
			newrecord.LVDataList[2].nReserved1 = enemy.Loc.X;
			newrecord.LVDataList[2].nReserved2 = enemy.Loc.Y; 
			newrecord.LVDataList[2].nReserved3 = enemy.Loc.Z; 
			
			newrecord.LVDataList[3].szData = record.LVDataList[3].szData;
			newrecord.LVDataList[3].szTexture = record.LVDataList[3].szTexture;
			newrecord.LVDataList[3].nTextureWidth = record.LVDataList[3].nTextureWidth;
			newrecord.LVDataList[3].nTextureHeight = record.LVDataList[3].nTextureHeight;
				
			newrecord.LVDataList[4].szData = string(GetDistance( user.Loc, enemy.Loc ));
			
			if (enemy.bCanBeAttacked)
			{
				newrecord.LVDataList[1].szData = "PvP";
			}
			if (enemy.nCriminalRate > 0)
			{
				newrecord.LVDataList[1].szData = "PK";
			}
			
			Me.ModifyRecord(i, newrecord);
		}	
		t_All.SetText("Player count: " $ string(counterAll));
		t_War.SetText("War count: " $ string(counterWar));
		
		h_Timer.SetTimer(3328, 1000);
	}
}

function bool GetSelectedListCtrlItem(out LVDataRecord record)
{
	local int index;
	index = Me.GetSelectedIndex();
	if( index >= 0 )
	{
		Me.GetRec(index, record);
		return true;
	}
	return false;
}

function OnDBClickListCtrlRecord( String strID )
{
	local LVDataRecord record;
	local string Name;
	local vector Pos;
	
	if (strID == "MainList")
	{
		if( GetSelectedListCtrlItem( record ) )
		{
			Name = record.LVDataList[2].szData;
			Pos.X = record.LVDataList[2].nReserved1;
			Pos.Y = record.LVDataList[2].nReserved2;
			Pos.Z = record.LVDataList[2].nReserved3;
			if (class'UIAPI_CHECKBOX'.static.IsChecked("WarMemberWarning.Options.arrowCheck"))
				class'QuestAPI'.static.SetQuestTargetInfo( true, true, true, Name, Pos, -1);
			RequestTargetUser(record.LVDataList[0].nReserved1);
		}
	}
}

function bool isEnemyClan(UserInfo Info, UserInfo pInfo)
{
	local bool enemyClanMember;
	local int warFromCount;
	local int warToCount;
	local int i, j, k;
	
	local bool isFromExist;
	local bool isToExist;
	local bool isWarNameExist;
	
	local LVDataRecord rec;
	
	if ( Info.nID != pInfo.nID && !Info.bNpc && !Info.bPet && Info.nClanID > 0)
	{
		warFromCount = WarFromMyClan.GetRecordCount();
		warToCount = WarToMyClan.GetRecordCount();
		
		//sysDebug("war from my clan - " $ string(warFromCount));
		//sysDebug("war to my clan - " $ string(warToCount));
		
		rec.LVDataList.Length = 3;
		
		for (i = 0; i < warFromCount; i++)
		{
			WarFromMyClan.GetRec(i,rec);
				
			if (rec.LVDataList[0].szData == class'UIDATA_CLAN'.static.GetName(Info.nClanID))
			{
				isFromExist = true;
				//sysDebug("mb odnostoronka - ");
				
				for (j = 0; j < warToCount; j++)
				{
					WarToMyClan.GetRec(j,rec);
					
					if (rec.LVDataList[0].szData == class'UIDATA_CLAN'.static.GetName(Info.nClanID))
					{
						isToExist = true;
						//sysDebug("dvustoronka - " $ rec.LVDataList[0].szData);
						if (isFromExist && isToExist)
						{
							enemyClanMember = true;
							for (k = 0; k < WarClanNames.Length; k++)
							{
								if (class'UIDATA_CLAN'.static.GetName(Info.nClanID) == WarClanNames[k])
								{
									isWarNameExist = true;
								}
							}
							
							if (!isWarNameExist)
							{
								WarClanNames[WarClanNames.Length - 1] = class'UIDATA_CLAN'.static.GetName(Info.nClanID);
							}
						}
					}	
				}
			}
				
		}
		
		return enemyClanMember;
	}
	
	return false;
}

function CheckPlayer(string param)
{
	local UserInfo Info;
	local UserInfo warInfo;
	local string clanName;
	
	local LVDataRecord CheckRec;
	
	local int i;
	local int j;
	local int AttID;
	
	local bool isPlayerExist;
	local bool enemyClanMember;
	local bool pinkFlag;
	local bool PK;
	local bool name0;
	
	isPlayerExist = false;
	
	ParseInt(param, "AttackerID", AttID);
	
	GetPlayerInfo(Info);
	GetUserInfo(AttID, warInfo);
	
	enemyClanMember = false;
	pinkFlag = false;
	PK = false;
	name0 = false;
	
	if (Info.nClanID > 0 && warInfo.nClanID == Info.nClanID)
		return;
	
	if (GetPartyMemberCount() > 0)
	{
		for (i = 0; i < GetPartyMemberCount(); i++)
			if (warInfo.nID == script_pt.m_arrID[i])
				return;
	}
	
	enemyClanMember = isEnemyClan(warInfo, Info);
	
	if (warInfo.nID != Info.nID && !warInfo.bNpc && !warInfo.bPet && warInfo.bCanBeAttacked )
		pinkFlag = true;
	
	if (warInfo.nID != Info.nID && !warInfo.bNpc && !warInfo.bPet && warInfo.nCriminalRate > 0 )
		PK = true;
	
	for (i = 0; i < 9; i++)
		if (e_name[i].GetString() != "" && e_name[i].GetString() == warInfo.Name)
			name0 = true;
	
	if ( enemyClanMember || pinkFlag || PK || name0 )
	{
		
		for (i = 0; i < PlayerNames.Length; i++)
			if (PlayerNames[i] == warInfo.Name)
			{
				for (j = 0; j < Me.GetRecordCount(); j++)
				{
					Me.GetRec(j, CheckRec);
					if(CheckRec.LVDataList[2].szData == warInfo.Name) 
					{
						return;
						//sysDebug("EX");
					}
				}
				PlayerNames.Remove(i, 1);
			}
		
		//PlayerNames.Insert(PlayerNames.Length - 1, 1);
		PlayerNames[ PlayerNames.Length - 1 ] = warInfo.Name;

		clanName = class'UIDATA_CLAN'.static.GetName(warInfo.nClanID);
		
		if (enemyClanMember)
		{
			counterWar += 1;
		}
			
		//Have to set length before all
		mainRec.LVDataList.Length = 5;
		mainRec.LVDataList[0].arrTexture.Length = 2;
		
		if (class'UIDATA_CLAN'.static.GetCrestTexture(warInfo.nClanID, ClanTexObj))
		{
			mainRec.LVDataList[0].arrTexture[0].objTex = ClanTexObj;
			mainRec.LVDataList[0].arrTexture[0].X = 12;
			mainRec.LVDataList[0].arrTexture[0].Y = 0;
			mainRec.LVDataList[0].arrTexture[0].Width = 16;
			mainRec.LVDataList[0].arrTexture[0].Height = 12;
			mainRec.LVDataList[0].arrTexture[0].U = 0;
			mainRec.LVDataList[0].arrTexture[0].V = 4;
			mainRec.LVDataList[0].arrTexture[0].UL = 16;
			mainRec.LVDataList[0].arrTexture[0].VL = 12;
		}
		else
		{
			mainRec.LVDataList[0].arrTexture[0].objTex = ClanTexObj;
			mainRec.LVDataList[0].arrTexture[0].X = 0;
			mainRec.LVDataList[0].arrTexture[0].Y = 0;
			mainRec.LVDataList[0].arrTexture[0].Width = 0;
			mainRec.LVDataList[0].arrTexture[0].Height = 0;
			mainRec.LVDataList[0].arrTexture[0].U = 0;
			mainRec.LVDataList[0].arrTexture[0].V = 0;
			mainRec.LVDataList[0].arrTexture[0].UL = 0;
			mainRec.LVDataList[0].arrTexture[0].VL = 0;
			mainRec.LVDataList[0].nReserved1 = 0;
		}
		
		if (class'UIDATA_CLAN'.static.GetAllianceCrestTexture(warInfo.nClanID, AllianceTexObj))
		{
			mainRec.LVDataList[0].arrTexture[1].objTex = AllianceTexObj;
			mainRec.LVDataList[0].arrTexture[1].X = 4;
			mainRec.LVDataList[0].arrTexture[1].Y = 0;
			mainRec.LVDataList[0].arrTexture[1].Width = 8;
			mainRec.LVDataList[0].arrTexture[1].Height = 12;
			mainRec.LVDataList[0].arrTexture[1].U = 0;
			mainRec.LVDataList[0].arrTexture[1].V = 4;
			mainRec.LVDataList[0].arrTexture[1].UL = 8;
			mainRec.LVDataList[0].arrTexture[1].VL = 12;
		}
		else
		{
			mainRec.LVDataList[0].arrTexture[1].objTex = AllianceTexObj;
			mainRec.LVDataList[0].arrTexture[1].X = 0;
			mainRec.LVDataList[0].arrTexture[1].Y = 0;
			mainRec.LVDataList[0].arrTexture[1].Width = 0;
			mainRec.LVDataList[0].arrTexture[1].Height = 0;
			mainRec.LVDataList[0].arrTexture[1].U = 0;
			mainRec.LVDataList[0].arrTexture[1].V = 0;
			mainRec.LVDataList[0].arrTexture[1].UL = 0;
			mainRec.LVDataList[0].arrTexture[1].VL = 0;
		}
		
		mainRec.LVDataList[0].nReserved1 = warInfo.nID;
		mainRec.LVDataList[0].szData = class'UIDATA_CLAN'.static.GetName(warInfo.nClanID);
		
		mainRec.LVDataList[2].szData = warInfo.Name;
		if (pinkFlag)
		{
			mainRec.LVDataList[1].szData = "PvP";
		}
		if (PK)
		{
			mainRec.LVDataList[1].szData = "PK";
		}
		if (!pinkFlag && !PK)
		{
			mainRec.LVDataList[1].szData = "N";
		}
		mainRec.LVDataList[2].nReserved1 = warInfo.Loc.X;
		mainRec.LVDataList[2].nReserved2 = warInfo.Loc.Y;
		mainRec.LVDataList[2].nReserved3 = warInfo.Loc.Z;
		
		mainRec.LVDataList[3].szData = string(warInfo.nSubClass);
		mainRec.LVDataList[3].szTexture = GetClassIconName(warInfo.nSubClass);
		mainRec.LVDataList[3].nTextureWidth = 11;
		mainRec.LVDataList[3].nTextureHeight = 11;
			
		mainRec.LVDataList[4].szData = string(GetDistance( Info.Loc, warInfo.Loc ));
			
		class'UIAPI_LISTCTRL'.static.InsertRecord( "WarMemberWarning.MainList", mainRec );
		
		for (i = 0; i < Me.GetRecordCount(); i++)
		{
			PlayerNames.Length = Me.GetRecordCount();
			Me.GetRec( i, CheckRec);
			PlayerNames[i] = CheckRec.LVDataList[2].szData;
		}
	
	}
}

function HandleClanWarList( String a_Param )
{
	local string sClanName;
	local int type;			// 0 : 선포, 1: 피선포, 2:쌍방선포

	ParseString( a_Param, "ClanName", sClanName );
	ParseInt( a_Param, "Type", type );

	if( type == 1 )
	{
		//sysDebug( "type 1 - " $ sClanName );
	}
	else
	{
		//sysDebug( "type 0 or 2 - " $ sClanName );
	}
}

function int GetDistance( vector a, vector b )
{
	local int distance;

	distance = VSize(b - a);
	
	return distance;
}
defaultproperties
{
}
