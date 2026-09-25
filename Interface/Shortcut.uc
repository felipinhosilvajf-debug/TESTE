class Shortcut extends UIScriptEx;

var bool m_chatstateok;

const CHAT_WINDOW_NORMAL = 0;
const CHAT_WINDOW_TRADE = 1;
const CHAT_WINDOW_PARTY = 2;
const CHAT_WINDOW_CLAN = 3;
const CHAT_WINDOW_ALLY = 4;
const CHAT_WINDOW_COUNT = 5;
const CHAT_WINDOW_SYSTEM = 5;		// 시스템 메시지 창

function OnRegisterEvent()
{
	RegisterEvent( EV_ShortcutCommand );
	RegisterEvent( EV_StateChanged );		// lpislhy
	RegisterEvent( EV_ShowWindow );
}

function OnLoad()
{
	if(CREATE_ON_DEMAND==0)
		OnRegisterEvent();
}

function OnEvent( int a_EventID, String a_Param )
{
	switch( a_EventID )
	{
	case EV_ShortcutCommand:
		HandleShortcutCommand( a_Param );
		break;
	case EV_StateChanged:
		HandleStateChange( a_Param );
		break;
	case EV_ShowWindow:
		HandleShortcutKeyEvent( a_Param );
		break;
	default:
		break;
	}
}

function HandleShortcutKeyEvent( string a_Param )
{
	local string WNDName;
	local OptionWnd o_script;
	local TargetStatusWnd t_script;
	local ShortcutWnd s_script;
	local WindowHandle TempWnd;
	local WindowHandle WNDNameHandle;
	ParseString(a_Param, "Name", WNDName);
	o_script = OptionWnd( GetScript( "OptionWnd" ) );
	t_script = TargetStatusWnd( GetScript("TargetStatusWnd") );
	s_script = ShortcutWnd( GetScript("ShortcutWnd") );
	
	if(CREATE_ON_DEMAND==0)
		WNDNameHandle = GetHandle(WndName);
	else
		WNDNameHandle = GetWindowHandle(WndName);
	
	switch (WNDName)
	{
		case "GMWnd":
		case "GMClanWnd":
		case "GMDetailStatusWnd":
		case "GMInventoryWnd":
		case "GMMagicSkillWnd":
		case "GMQuestWnd":
		case "GMWarehouseWnd":
		case "GMSnoopWnd":
		case "GMPetitionWnd":
			if( !IsBuilderPC() )
				return;
		break;
	}
	
	switch (WNDName)
	{
		case "InventoryWnd":
			ExecuteEvent(EV_InventoryToggleWindow);	
			break;
		case "MacroWnd":
			ExecuteEvent(EV_MacroShowListWnd);
			break;
		case "PartyMatchWnd":
			HandlePartyMatchingOnOff();
			break;
		case "BoardWnd":
			if(CREATE_ON_DEMAND==0)
				TempWnd=GetHandle("BoardWnd");
			else
				TempWnd=GetWindowHandle("BoardWnd");

			if (TempWnd.isShowWindow())
				TempWnd.HideWindow();
			else 
				ExecuteEvent(EV_ShowBBS);
			
			break;
		case "MinimapWnd":
			RequestOpenMinimap();
			break;
		
		case "HelpHtmlWnd":
			HandleShowHelpHtmlWnd();
			break;
		
		case "FN_HideDropItemSilhauette":
			if (GetOptionBool("Game", "HideDropItem"))
			{
				SetOptionBool( "Game", "HideDropItem", false );
			}
			else
			{
				SetOptionBool( "Game", "HideDropItem", true );
			}
			o_script.InitGameOption();
			break;
		case "FN_SendTargetedCharacterMessage":
			if (t_script.g_NameStr == "")
			{
			}
			else 
			{
				SetChatMessage( "\"" $ t_script.g_NameStr $ " " );
			}
			break;
			//
		case "FN_MuteAllAudio":
			if (GetOptionBool("Audio", "AudioMuteOn"))
			{
				SetOptionBool( "Audio", "AudioMuteOn", false );
			}
			else
			{
				SetOptionBool( "Audio", "AudioMuteOn", true );
			}
			o_script.InitAudioOption();
			break;
		case "FN_SHORTCUTEXPAND":
			s_script.OnClickExpandShortcutButton();
			break;
		case "FN_UILocReset":
			o_script.OnClickButton("WindowInitBtn");
			break;
		Default:
			if(WNDNameHandle.IsShowWindow())
			{
				WNDNameHandle.HideWindow();
				PlaySound("InterfaceSound.charstat_close_01");
			}
			else
			{
				WNDNameHandle.ShowWindow();
				WNDNameHandle.SetFocus();
				PlaySound("InterfaceSound.charstat_open_01");
			}
		break;
	}
}

function ClosePartyMatchingWnd()
{
	local WindowHandle TaskWnd;
	local PartyMatchWnd p_script;
	p_script = PartyMatchWnd( GetScript( "PartyMatchWnd" ) );
	
	if(CREATE_ON_DEMAND==0)
		TaskWnd = GetHandle("PartyMatchWnd");
	else
		TaskWnd = GetWindowHandle("PartyMatchWnd");

	TaskWnd.HideWindow();
	p_script.OnSendPacketWhenHiding();
}


function HandleShowHelpHtmlWnd()
{
	local  AgeWnd script1;	// 등급표시 스크립트 가져오기
	
	local string strParam;
	ParamAdd(strParam, "FilePath", "..\\L2text\\help.htm");
	ExecuteEvent(EV_ShowHelp, strParam);
	
	script1 = AgeWnd( GetScript("AgeWnd") );
	
	if(script1.bBlock == false)	script1.startAge();	//등급표시를 켜준다. 
}

function HandlePartyMatchingOnOff()
{
	local WindowHandle TaskWnd;
	local PartyMatchRoomWnd p2_script;
	p2_script = PartyMatchRoomWnd( GetScript( "PartyMatchRoomWnd" ) );

	if(CREATE_ON_DEMAND==0)
		TaskWnd=GetHandle("PartyMatchWnd");
	else
		TaskWnd=GetWindowHandle("PartyMatchWnd");

	if(TaskWnd.IsShowWindow())
	{
		ClosePartyMatchingWnd();
	}
	else
	{
		if(CREATE_ON_DEMAND==0)
			TaskWnd=GetHandle("PartyMatchRoomWnd");
		else
			TaskWnd=GetWindowHandle("PartyMatchRoomWnd");

		if (TaskWnd.IsShowWindow())
		{
			TaskWnd.HideWindow();
			
			if(CREATE_ON_DEMAND==0)
				TaskWnd=GetHandle("PartyMatchWnd");
			else
				TaskWnd=GetWindowHandle("PartyMatchWnd");

			p2_script.ExitPartyRoom();
			
			TaskWnd.SetTimer( 1991, 500 );
			
		}
		else
		{
			if(CREATE_ON_DEMAND==0)
				TaskWnd=GetHandle("PartyMatchWnd");
			else
				TaskWnd=GetWindowHandle("PartyMatchWnd");
			//~ TaskWnd.ShowWindow();
			class'PartyMatchAPI'.static.RequestOpenPartyMatch();
		}
	}
}

function OnTimer(int TimerID)
{
	if(TimerID == 1991)
	{
		ClosePartyMatchingWnd();
		class'UIAPI_WINDOW'.static.KillUITimer("ShortcutTab",1991); 
	}
}

function OnExitState( name a_NextStateName )
{
	if (a_NextStateName == 'GamingState')
	{
		//debug("what?");
		m_chatstateok = true;
	}
	else
	{
		m_chatstateok = false;
	}
}


function HandleShortcutCommand( String a_Param )
{
	local String Command;
	
	if( ParseString( a_Param, "Command", Command ) )
	{
		switch( Command )
		{
		case "CloseAllWindow":		
			HandleCloseAllWindow();
			break;
		case "ShowChatWindow":		// alt + j
			HandleShowChatWindow();
			//if (m_chatstateok == true)
			//{
			//	HandleShowChatWindow();
			//}
			break;
		case "SetPrevChatType":		// alt + page up	
			HandleSetPrevChatType();
			break;
		case "SetNextChatType":		// alt + page down
			HandleSetNextChatType();
			break;
		case "shortcutreset":
			class'ShortcutAPI'.static.RestoreDefault();
			break;
		case "shortcutsave":
			class'ShortcutAPI'.static.Save();
			break;
		case "shortcutload":
			class'ShortcutAPI'.static.RequestList();
			break;
		case "test":
			HandleShortcutTest();
			break;
		case "printshortcut":
			HandlePrintShortcut();
			break;
		case "TargetLockUnlock":
			ToggleTarget();
			break;
		default:
			break;
		}
	}
}

function ToggleTarget()
{
	local OlyDmgOptionWnd script_olyopt;
	script_olyopt = OlyDmgOptionWnd(GetScript("OlyDmgOptionWnd"));
	
	script_olyopt.OnLockTarget();
}

function HandlePrintShortcut()
{
	local Array<ShortcutCommandItem> commandlist;
	local Array<string> grouplist;
	local int i,j;
	class'ShortcutAPI'.static.GetGroupList(groupList);
	for( i = 0 ; i < grouplist.Length ; ++i )
	{
		//debug("Group : " $ grouplist[i] );
		commandlist.Length = 0;
		class'ShortcutAPI'.static.GetGroupCommandList(grouplist[i], commandlist);
		for( j=0 ; j < commandlist.Length ; ++j )
			//debug("key : " $ commandlist[j].Key $ ", subkey1 : " $ commandlist[j].subkey1 $ ", subkey2 : " $ commandlist[j].subkey2 $ ", action : " $ commandlist[j].sAction $ ", command : " $ commandlist[j].sCommand $ ", id : " $ commandlist[j].id);
	}
	grouplist.Length = 0;
	class'ShortcutAPI'.static.GetActiveGroupList(grouplist);
	for(i=0 ; i<grouplist.Length ; ++i)
	{
		//debug("ActiveGroup : " $ grouplist[i]);
	}
}
//~ function HandleShortcutTest()
//~ {
	//~ local ShortcutCommandItem item;
	//~ local array<ShortcutCommandItem> items;
	//~ local int i;
	//~ item.sCommand = "ZoomIn";
	//~ item.key = "MouseWheelUp";
	//~ item.subkey1 = "";
	//~ item.subkey2 = "";
	//~ item.sState = "GamingState";
	//~ if( class'ShortcutAPI'.static.AssignShortcut(item) )
		//~ Log("ShortcutAssign Success ZoomIn");
	//~ item.sCommand = "ZoomOut";
	//~ item.key = "MouseWheelDown";
	//~ item.subkey1 = "";
	//~ item.subkey2 = "";
	//~ item.sState = "GamingState";
	//~ if( class'ShortcutAPI'.static.AssignShortcut(item) )
		//~ Log("ShortcutAssign Success ZoomOut");
	//~ item.sCommand = "TurnBack";
	//~ item.key = "MiddleMouse";
	//~ item.subkey1 = "";
	//~ item.subkey2 = "";
	//~ item.sState = "GamingState";
	//~ if( class'ShortcutAPI'.static.AssignShortcut(item) )
		//~ Log("ShortcutAssign Success TurnBack" );

	//~ item.sCommand = "ShowInventoryWindow";
	//~ item.key = "i";
	//~ item.subkey1 = "alt";
	//~ item.subkey2 = "";
	//~ item.sState = "GamingState";
	//~ if( class'ShortcutAPI'.static.AssignShortcut(item) )
		//~ Log("ShortcutAssign Success TurnBack ShowInventoryWindow" );

	//~ item.sCommand = "PKKey";
	//~ item.key = "Ctrl";
	//~ item.subkey1 = "";
	//~ item.subkey2 = "";
	//~ item.sState = "";
	//~ item.sCategory = "";
	//~ if( class'ShortcutAPI'.static.AssignSpecialKey(item) )
		//~ Log("SpecialKeyAssign Success PKKey");

	//~ class'ShortcutAPI'.static.GetCommandItems(items);
	//~ for(i=0 ; i<items.Length ; ++i)
	//~ {
		//~ Log("Shortcut " $ i $ ": key(" $ items[i].key $ "), subkey1(" $ items[i].subkey1 $ "), subkey2(" $ items[i].subkey2 $ "), command(" $ items[i].sCommand $ "), state(" $ items[i].sState $ "), category(" $ items[i].sCategory $ ")");
	//~ }
//~ }

function HandleShortcutTest()
{
}

function HandleShowChatWindow()		// alt + j
{
	local WindowHandle handle;

	if(CREATE_ON_DEMAND==0)
		handle = GetHandle( "ChatWnd" );
	else
		handle = GetWindowHandle( "ChatWnd" );
	
	if( handle.IsShowWindow() )
	{
		handle.HideWindow();
		if( GetOptionBool("Game", "SystemMsgWnd") )
			class'UIAPI_WINDOW'.static.HideWindow("SystemMsgWnd");
			
	}
	else
	{
		handle.ShowWindow();
		if( GetOptionBool("Game", "SystemMsgWnd") )
			class'UIAPI_WINDOW'.static.ShowWindow("SystemMsgWnd");
	}
}

function HandleSetPrevChatType()		// alt + page up
{
	local ChatWnd chatWndScript;			// 채팅 윈도우 클래스
	
	chatWndScript = ChatWnd( GetScript("ChatWnd") );	// 스크립트를 가져온다.
	
	//debug("chatWndScript.m_chatType" $ chatWndScript.m_chatType);
	switch (chatWndScript.m_chatType.UI)	
	{
		case CHAT_WINDOW_NORMAL:
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(4, true);
			chatWndScript.HandleTabClick("ChatTabCtrl4");
			break;
		case CHAT_WINDOW_TRADE:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(0, true);
			chatWndScript.HandleTabClick("ChatTabCtrl0");
			break;
		case CHAT_WINDOW_PARTY:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(1, true);
			chatWndScript.HandleTabClick("ChatTabCtrl1");
			break;
		case CHAT_WINDOW_CLAN:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(2, true);
			chatWndScript.HandleTabClick("ChatTabCtrl2");
			break;
		case CHAT_WINDOW_ALLY:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(3, true);
			chatWndScript.HandleTabClick("ChatTabCtrl3");
			break;		
	}

}

function HandleSetNextChatType()		// alt + page down
{
	local ChatWnd chatWndScript;			// 채팅 윈도우 클래스
	
	chatWndScript = ChatWnd( GetScript("ChatWnd") );	// 스크립트를 가져온다.
	
	//debug("chatWndScript.m_chatType" $ chatWndScript.m_chatType);
	switch (chatWndScript.m_chatType.UI)	
	{
		case CHAT_WINDOW_NORMAL:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(1, true);
			chatWndScript.HandleTabClick("ChatTabCtrl1");
			break;
		case CHAT_WINDOW_TRADE:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(2, true);
			chatWndScript.HandleTabClick("ChatTabCtrl2");
			break;
		case CHAT_WINDOW_PARTY:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(3, true);
			chatWndScript.HandleTabClick("ChatTabCtrl3");
			break;
		case CHAT_WINDOW_CLAN:
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(4, true);
			chatWndScript.HandleTabClick("ChatTabCtrl4");
			break;
		case CHAT_WINDOW_ALLY:
			//chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_NORMAL);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_TRADE);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_PARTY);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_CLAN);
			chatWndScript.ChatTabCtrl.MergeTab(CHAT_WINDOW_ALLY);
			chatWndScript.ChatTabCtrl.SetTopOrder(0, true);
			chatWndScript.HandleTabClick("ChatTabCtrl0");
			break;		
	}
}


function HandleCloseAllWindow()
{
	local WindowHandle handle;
	local int i;
	local array<string> WndList;
	
	WndList[0]="ActionWnd";
	WndList[1]="AttributeEnchantWnd";
	WndList[2]="AttributeRemoveWnd";
	WndList[3]="BoardWnd";
	WndList[4]="CalculatorWnd";
	WndList[5]="ChatFilterWnd";
	WndList[6]="ClanWnd";
	WndList[7]="ConsoleWnd";
	WndList[8]="CouponEventWnd";
	WndList[9]="DeliverWnd";
	WndList[10]="SelectDeliverWnd";
	WndList[11]="DetailStatusWnd";
	WndList[12]="MailBtnWnd";
	WndList[13]="HelpHtmlWnd";
	WndList[14]="HennaInfoWnd";
	WndList[15]="HennaListWnd";
	WndList[16]="HeroTowerWnd";
	WndList[17]="InventoryWnd";
	WndList[18]="ItemEnchantWnd";
	WndList[19]="XMasSealWnd";
	WndList[20]="MacroEditWnd";
	WndList[21]="MacroInfoWnd";
	WndList[22]="MacroListWnd";
	WndList[23]="MagicSkillWnd";
	WndList[24]="MainWnd";
	WndList[25]="ManorCropInfoChangeWnd";
	WndList[26]="ManorCropInfoSettingWnd";
	WndList[27]="ManorCropSellChangeWnd";
	WndList[28]="ManorCropSellWnd";
	WndList[29]="ManorInfo_Crop";
	WndList[30]="ManorInfo_Default";
	WndList[31]="ManorInfo_Seed";
	WndList[32]="ManorInfoWnd";
	WndList[33]="ManorSeedInfoChangeWnd";
	WndList[34]="ManorSeedInfoSettingWnd";
	WndList[35]="ManorShopWnd";
	WndList[36]="MinimapWnd";
	WndList[37]="MinimapWnd_Expand";
	WndList[38]="MoviePlayerWnd";
	WndList[39]="MultiSellWnd";
	WndList[40]="OptionWnd";
	WndList[41]="PetitionFeedBackWnd";
	WndList[42]="PetitionWnd";
	WndList[43]="UserPetitionWnd";
	WndList[44]="PetWnd";
	WndList[45]="PrivateShopWnd";
	WndList[46]="QuestListWnd";
	WndList[47]="QuestTreeWnd";
	WndList[48]="RadarOptionWnd";
	WndList[49]="RecipeBookWnd";
	WndList[50]="RecipeBuyListWnd";
	WndList[51]="RecipeBuyManufactureWnd";
	WndList[52]="RecipeManufactureWnd";
	WndList[53]="RecipeShopWnd";
	WndList[54]="RecipeTreeWnd";
	WndList[55]="RefineryWnd";
	WndList[56]="ReplayListWnd";
	WndList[57]="ReplayLogoWnd";
	WndList[58]="ScenePlayerWnd";
	WndList[59]="ShopWnd";
	WndList[60]="SiegeInfoWnd";
	WndList[61]="SkillEnchantInfoWnd";
	WndList[62]="SkillEnchantWnd";
	WndList[63]="SSQMainBoard";
	WndList[64]="SSQMainBoard_SSQMainEventWnd";
	WndList[65]="SSQMainBoard_SSQSealStatusWnd";
	WndList[66]="SSQMainBoard_SSQStatusWnd";
	WndList[67]="SummonedWnd";
	WndList[68]="SystemMenuWnd";
	WndList[69]="TownMapWnd";
	WndList[70]="TradeWnd";
	WndList[71]="SkillTrainClanTreeWnd";
	WndList[72]="SkillTrainInfoSubWndEnchant";
	WndList[73]="SkillTrainInfoSubWndNormal";
	WndList[74]="SkillTrainInfoWnd";
	WndList[75]="SkillTrainListWnd";
	WndList[76]="TutorialViewerWnd";
	WndList[77]="unrefineryWnd";
	WndList[78]="WarehouseWnd";

	if(CREATE_ON_DEMAND==0)
	{
		handle = GetHandle( WndList[i] );
		if( handle.IsShowWindow() )
			handle.HideWindow();
	}
	else
	{
		for (i=0;i<WndList.Length; ++i)
		{
			handle = GetWindowHandle( WndList[i] );
			if( handle.IsShowWindow() )
				handle.HideWindow();
		}
	}
}

function HandleStateChange( String state )
{
	local	FlightShipCtrlWnd			scriptShip;
	local	FlightTransformCtrlWnd		scriptTrans;
	
	scriptShip = FlightShipCtrlWnd ( GetScript("FlightShipCtrlWnd") );
	scriptTrans = FlightTransformCtrlWnd ( GetScript("FlightTransformCtrlWnd") ); 
	
	if( state == "GAMINGSTATE" )
	{
		if(GetOptionBool( "Game", "EnterChatting" ))
		{					
			class'ShortcutAPI'.static.ActivateGroup("TempStateShortcut");
		}
		
		// 향상된 셰이더 로딩때문에 추가
		if(scriptShip.isNowActiveFlightShipShortcut) 	// 비행정 조종 모드라면		
		{
			if(GetOptionBool( "Game", "EnterChatting" ))	{class'ShortcutAPI'.static.DeactivateGroup("TempStateShortcut");}
			class'ShortcutAPI'.static.ActivateGroup("FlightStateShortcut");
		}
		else if (scriptTrans.isNowActiveFlightTransShortcut ) // 비행 변신체 모드라면
		{
			
			if(GetOptionBool( "Game", "EnterChatting" ))	{class'ShortcutAPI'.static.DeactivateGroup("TempStateShortcut");}
			class'ShortcutAPI'.static.ActivateGroup("FlightTransformShortcut");
		}		
	}
}
defaultproperties
{
}
