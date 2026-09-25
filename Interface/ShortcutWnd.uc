class ShortcutWnd extends UICommonAPI;

const MAX_Page = 10;
const MAX_ShortcutPerPage = 12;
const MAX_ShortcutPerPage2 = 24;
const MAX_ShortcutPerPage3 = 36;
const MAX_ShortcutPerPage4 = 48;
const MAX_ShortcutPerPage5 = 60;
enum EJoyShortcut
{
	JOYSHORTCUT_Left,
	JOYSHORTCUT_Center,
	JOYSHORTCUT_Right,
};
var WindowHandle Me;
var ItemWindowHandle RBracelet;
var bool m_IsLocked;
var bool m_IsVertical;
var bool m_IsJoypad;
var bool m_IsJoypadExpand;
var bool m_IsJoypadOn;
var bool m_IsExpand1;
var bool m_IsExpand2;
var transient float test;
//i??i??(10.02.25)
var int CurrentShortcutPage;
var int CurrentShortcutPage2;
var int CurrentShortcutPage3;
var int CurrentShortcutPage4;
var int CurrentShortcutPage5;
var int CurrentShortcutPage6;
var bool m_IsExpand3;
var bool m_IsExpand4;
var bool m_IsExpand5;

var bool m_IsShortcutExpand;
var String m_ShortcutWndName;

var ButtonHandle btnOpenPanelH;
var ButtonHandle btnOpenPanel1H;
var ButtonHandle btnClosePanelH;
var ButtonHandle btnClosePanel1H;
var ButtonHandle btnInstanceH;
var ButtonHandle btnOlyH;

var ButtonHandle btnOpenPanelV;
var ButtonHandle btnOpenPanel1V;
var ButtonHandle btnClosePanelV;
var ButtonHandle btnClosePanel1V;
var ButtonHandle btnInstanceV;
var ButtonHandle btnOlyV;

var WindowHandle m_hFlightShipWnd;
var WindowHandle m_hFlightCtrlWnd;
var bool m_IsPanelOpened;
var bool m_IsPanelOpened1;

var ItemControlWnd script_items;
//native function float GetTotalSceneTime();

function OnRegisterEvent()
{
	RegisterEvent( EV_ShortcutUpdate );
	RegisterEvent( EV_ShortcutPageUpdate );
	RegisterEvent( EV_ShortcutJoypad );
	RegisterEvent( EV_ShortcutClear );
	RegisterEvent( EV_JoypadLButtonDown );
	RegisterEvent( EV_JoypadLButtonUp );
	RegisterEvent( EV_JoypadRButtonDown );
	RegisterEvent( EV_JoypadRButtonUp );
	RegisterEvent( EV_ShortcutCommandSlot );

	RegisterEvent( EV_ShortcutkeyassignChanged );
	
	RegisterEvent( EV_SetEnterChatting );
	RegisterEvent( EV_UnSetEnterChatting );
	
	RegisterEvent( EV_ShowWindow );
}

function OnLoad()
{
	local bool bMinTooltip;
	local Tooltip Script;

	Me = GetWindowHandle( "ShortcutWnd" );
	m_hFlightShipWnd=GetWindowHandle("FlightShipCtrlWnd");
	m_hFlightCtrlWnd=GetWindowHandle("FlightTransformCtrlWnd");
	
	
	btnInstanceH = GetButtonHandle("ShortcutWnd.ShortcutWndHorizontal.btnInstanceH");
	btnOlyH = GetButtonHandle("ShortcutWnd.ShortcutWndHorizontal.btnOlyH");
	btnOpenPanelH = GetButtonHandle("ShortcutWnd.ShortcutWndHorizontal.btnOpenPanelH");
	btnOpenPanel1H = GetButtonHandle("ShortcutWnd.ShortcutWndHorizontal.btnOpenPanel1H");
	btnClosePanelH = GetButtonHandle("ShortcutWnd.ShortcutWndHorizontal.btnClosePanelH");
	btnClosePanel1H = GetButtonHandle("ShortcutWnd.ShortcutWndHorizontal.btnClosePanel1H");
	
	btnInstanceV = GetButtonHandle("ShortcutWnd.ShortcutWndVertical.btnInstanceV");
	btnOlyV = GetButtonHandle("ShortcutWnd.ShortcutWndVertical.btnOlyV");
	btnOpenPanelV = GetButtonHandle("ShortcutWnd.ShortcutWndVertical.btnOpenPanelV");
	btnOpenPanel1V = GetButtonHandle("ShortcutWnd.ShortcutWndVertical.btnOpenPanel1V");
	btnClosePanelV = GetButtonHandle("ShortcutWnd.ShortcutWndVertical.btnClosePanelV");
	btnClosePanel1V = GetButtonHandle("ShortcutWnd.ShortcutWndVertical.btnClosePanel1V");
	
	RBracelet = GetItemWindowHandle( "InventoryWnd.EquipItem_RBracelet" );
	
	script_items = ItemControlWnd(GetScript("ItemControlWnd"));

	//Load Ini
	m_IsLocked = GetOptionBool( "Game", "IsLockShortcutWnd" );
	m_IsExpand1 = GetOptionBool( "Game", "Is1ExpandShortcutWnd" );
	m_IsExpand2 = GetOptionBool( "Game", "Is2ExpandShortcutWnd" );
	m_IsVertical = GetOptionBool( "Game", "IsShortcutWndVertical" );
	m_IsExpand3 = GetOptionBool( "Game", "Is3ExpandShortcutWnd" );
	m_IsExpand4 = GetOptionBool( "Game", "Is4ExpandShortcutWnd" );
	m_IsExpand5 = GetOptionBool( "Game", "Is5ExpandShortcutWnd" );
	

	InitShortPageNum();

	bMinTooltip = GetOptionBool( "Game", "IsShortcutWndMinTooltip" );
	Script = Tooltip( GetScript( "Tooltip" ) );
	Script.setBoolSelect( !bMinTooltip );
	
	if( bMinTooltip )
	{
		HideWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMaxBtn" );
		ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMinBtn" );
		HideWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMaxBtn" );
		ShowWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMinBtn" );
	}
	else
	{
		ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMaxBtn" );
		HideWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMinBtn" );
		ShowWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMaxBtn" );
		HideWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMinBtn" );
	}
	
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.btnOpenPanelH" );
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.btnOpenPanel1H" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.btnOpenPanelV" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.btnOpenPanel1V" );
	
	btnClosePanelH.SetTooltipCustomType(MakeTooltipSimpleText("Close panel" ));
	btnOpenPanelH.SetTooltipCustomType(MakeTooltipSimpleText("Open panel" ));
	btnClosePanel1H.SetTooltipCustomType(MakeTooltipSimpleText("Close panel" ));
	btnOpenPanel1H.SetTooltipCustomType(MakeTooltipSimpleText("Open panel" ));
	btnInstanceH.SetTooltipCustomType(MakeTooltipSimpleText("Instance Zone" ));
	btnOlyH.SetTooltipCustomType(MakeTooltipSimpleText("Olympiad Arena" ));

	btnClosePanelV.SetTooltipCustomType(MakeTooltipSimpleText("Close panel" ));
	btnOpenPanelV.SetTooltipCustomType(MakeTooltipSimpleText("Open panel" ));
	btnClosePanel1V.SetTooltipCustomType(MakeTooltipSimpleText("Close panel" ));
	btnOpenPanel1V.SetTooltipCustomType(MakeTooltipSimpleText("Open panel" ));
	btnInstanceV.SetTooltipCustomType(MakeTooltipSimpleText("Instance Zone" ));
	btnOlyV.SetTooltipCustomType(MakeTooltipSimpleText("Olympiad Arena" ));
	
	m_IsPanelOpened = GetOptionBool( "Game", "IsOpendFlightShipWnd" );
	m_IsPanelOpened1 = GetOptionBool( "Game", "IsOpendFlightShipWnd1" );
	
}

function bool GetEquippedBracelet()
{
	local ItemInfo EquippedBracelet;

	if (RBracelet.GetItem(0, EquippedBracelet))
		return true;
	else 
		return false;
}

function OnDefaultPosition()
{
	if (GetOptionInt( "Game", "LayoutDF" ) == 1)
	{
		m_IsExpand1 = true;
		m_IsExpand2 = true;
		//i??i??(10.02.25)
		m_IsExpand3 = true;
		m_IsExpand4 = true;
		m_IsExpand5 = true;
	}
	else
	{
	//~ class'UIAPI_WINDOW'.static.ClearAnchor( "ShortcutWnd.ShortcutWndVertical" );
	//~ class'UIAPI_WINDOW'.static.SetAnchor( "ShortcutWnd.ShortcutWndHorizontal", "ShortcutWnd.ShortcutWndVertical", "BottomRight", "BottomRight", 0, 0 );
	}
	ArrangeWnd();
	expandWnd();
	if (GetOptionInt( "Game", "LayoutDF" ) == 1)
	{
		SetVertical(false);
	}
}

function OnEnterState( name a_PreStateName )
{
	ArrangeWnd();
	ExpandWnd();
	
	if( a_PreStateName == 'LoadingState' )
	{		
		//sysDebug(string(m_IsPanelOpened));
		//sysDebug(string(m_IsPanelOpened1));
		InitShortPageNum();
	}
	if (!m_IsPanelOpened)
		CloseFlightShipWnd();
	else
		OpenFlightShipWnd();
		
	if (!m_IsPanelOpened1)
		CloseFlightCtrlWnd();
	else
		OpenFlightCtrlWnd();
	
	//HideWindow( "ShortcutWnd.ShortcutWndHorizontal.btnClosePanel" );
}

function OnEvent( int a_EventID, String a_Param )
{	
	// Debug("ShortcutWnd OnEvent a_EventID: " $ a_EventID $ " a_Param " $ a_Param);
	switch( a_EventID )
	{
	case EV_ShortcutCommandSlot:
		ExecuteShortcutCommandBySlot(a_Param);
		break;
	case EV_ShortcutPageUpdate:					//i©÷¡ìe©÷?i¡×¢¬i?i¡í¡¤i¡Æ¨öi? i??i¢¥i¡×¢æe¡Æ¢æ i??e¢®©« i?¢´i??e?i??i? e?¨« e??e¨ùi?¢´e?¡± i¢¥e©÷¢´i?¢¬
		HandleShortcutPageUpdate( a_Param );
		break;
	case EV_ShortcutJoypad:
		HandleShortcutJoypad( a_Param );
		break;
	case EV_JoypadLButtonDown:
		HandleJoypadLButtonDown( a_Param );
		break;
	case EV_JoypadLButtonUp:
		HandleJoypadLButtonUp( a_Param );
		break;
	case EV_JoypadRButtonDown:
		HandleJoypadRButtonDown( a_Param );
		break;
	case EV_JoypadRButtonUp:
		HandleJoypadRButtonUp( a_Param );
		break;
	case EV_ShortcutUpdate:
		HandleShortcutUpdate( a_Param );
		break;
	case EV_ShortcutClear:
		HandleShortcutClear();
		//InitShortPageNum();
		ArrangeWnd();
		ExpandWnd();
		break;

	case EV_ShortcutkeyassignChanged:		
	case EV_SetEnterChatting:
	case EV_UnSetEnterChatting:
		ClearAllShortcutItemTooltip();
		break;
	}
}

function ClearAllShortcutItemTooltip()
{
	Me.ClearAllChildShortcutItemTooltip();
}

function InitShortPageNum()
{
	CurrentShortcutPage = 0;
	CurrentShortcutPage2 = 1;
	CurrentShortcutPage3 = 2;
	CurrentShortcutPage4 = 3;
	CurrentShortcutPage5 = 4;
	CurrentShortcutPage6 = 5;
}

function HandleShortcutPageUpdate(string param)
{
	local int i;
	local int nShortcutID;
	local int ShortcutPage;

	if( ParseInt(param, "ShortcutPage", ShortcutPage) )
	{
		if( 0 > ShortcutPage || MAX_Page <= ShortcutPage )
			return;
		
		// Debug("HandleShortcutPageUpdate: " $ param $ " nShortcutID " $ nShortcutID);

		CurrentShortcutPage = ShortcutPage;
		class'UIAPI_TEXTBOX'.static.SetText( "ShortcutWnd." $ m_ShortcutWndName $ ".PageNumTextBox", string( CurrentShortcutPage + 1 ) );
		nShortcutID = CurrentShortcutPage * MAX_ShortcutPerPage;
		for( i = 0; i < MAX_ShortcutPerPage; ++i )
		{
			class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ ".Shortcut" $ ( i + 1 ), nShortcutID );
			nShortcutID++;
		}
	}
}

function HandleShortcutUpdate(string param)
{
	local int nShortcutID;
	local int nShortcutNum;
	
	ParseInt(param, "ShortcutID", nShortcutID);
	nShortcutNum = ( nShortcutID % MAX_ShortcutPerPage ) + 1;

	// Debug("HandleShortcutUpdate: " $ param $ " nShortcutID " $ nShortcutID);
	
	if( IsShortcutIDInCurPage( CurrentShortcutPage, nShortcutID ) )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ ".Shortcut" $ nShortcutNum, nShortcutID );
	}
	if( IsShortcutIDInCurPage( CurrentShortcutPage2, nShortcutID ) )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "_1.Shortcut" $ nShortcutNum, nShortcutID );
	}
	if( IsShortcutIDInCurPage( CurrentShortcutPage3, nShortcutID ) )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "_2.Shortcut" $ nShortcutNum, nShortcutID );
	}
	if( IsShortcutIDInCurPage( CurrentShortcutPage4, nShortcutID ) )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "_3.Shortcut" $ nShortcutNum, nShortcutID );
	}
	if( IsShortcutIDInCurPage( CurrentShortcutPage5, nShortcutID ) )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "_4.Shortcut" $ nShortcutNum, nShortcutID );
	}
	if( IsShortcutIDInCurPage( CurrentShortcutPage6, nShortcutID ) )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "_5.Shortcut" $ nShortcutNum, nShortcutID );
	}
}

function HandleShortcutClear()
{
	local int i;
	
	for( i=0 ; i < MAX_ShortcutPerPage ; ++i )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndVertical.Shortcut" $ (i+1) );
		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndVertical_1.Shortcut" $ (i+1) );
		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndVertical_2.Shortcut" $ (i+1) );
		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndHorizontal.Shortcut" $ (i+1) );
		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndHorizontal_1.Shortcut" $ (i+1) );
		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndHorizontal_2.Shortcut" $ (i+1) );

		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndJoypadExpand.Shortcut" $ (i+1) );
	}
	for( i=0; i< 4 ; ++i )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.Clear( "ShortcutWnd.ShortcutWndJoypad.Shortcut" $ (i+1) );
	}
}

function HandleShortcutJoypad( String a_Param )
{
	local int OnOff;

	if( ParseInt( a_Param, "OnOff", OnOff ) )
	{
		if( 1 == OnOff )
		{
			m_IsJoypadOn = true;
			if( Len(m_ShortcutWndName) > 0 )
				ShowWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".JoypadBtn" );
		}
		else if( 0 == OnOff )
		{
			m_IsJoypadOn = false;
			if( Len(m_ShortcutWndName) > 0 )
				HideWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".JoypadBtn" );
		}
	}
}

function HandleJoypadLButtonUp( String a_Param )
{
	SetJoypadShortcut( JOYSHORTCUT_Center );
}

function HandleJoypadLButtonDown( String a_Param )
{
	SetJoypadShortcut( JOYSHORTCUT_Left );
}

function HandleJoypadRButtonUp( String a_Param )
{
	SetJoypadShortcut( JOYSHORTCUT_Center );
}

function HandleJoypadRButtonDown( String a_Param )
{
	SetJoypadShortcut( JOYSHORTCUT_Right );
}

function SetJoypadShortcut( EJoyShortcut a_JoyShortcut )
{
	local int i;
	local int nShortcutID;

	switch( a_JoyShortcut )
	{
	case JOYSHORTCUT_Left:
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypadExpand.JoypadButtonBackTex", "L2UI_CH3.ShortcutWnd.joypad2_back_over1" );
		class'UIAPI_TEXTURECTRL'.static.SetAnchor( "ShortcutWnd.ShortcutWndJoypadExpand.JoypadButtonBackTex", "ShortcutWnd.ShortcutWndJoypadExpand", "TopLeft", "TopLeft", 28, 0 );
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypad.JoypadLButtonTex", "L2UI_ch3.Joypad.joypad_L_HOLD" );
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypad.JoypadRButtonTex", "L2UI_ch3.Joypad.joypad_R" );
		nShortcutID = CurrentShortcutPage * MAX_ShortcutPerPage + 4;
		for( i = 0; i < 4; ++i )
		{
			class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd.ShortcutWndJoypad.Shortcut" $ ( i + 1 ), nShortcutID );
			nShortcutID++;
		}
		break;
	case JOYSHORTCUT_Center:
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypadExpand.JoypadButtonBackTex", "L2UI_CH3.ShortcutWnd.joypad2_back_over2" );
		class'UIAPI_TEXTURECTRL'.static.SetAnchor( "ShortcutWnd.ShortcutWndJoypadExpand.JoypadButtonBackTex", "ShortcutWnd.ShortcutWndJoypadExpand", "TopLeft", "TopLeft", 158, 0 );
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypad.JoypadLButtonTex", "L2UI_ch3.Joypad.joypad_L" );
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypad.JoypadRButtonTex", "L2UI_ch3.Joypad.joypad_R" );
		nShortcutID = CurrentShortcutPage * MAX_ShortcutPerPage;
		for( i = 0; i < 4; ++i )
		{
			class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd.ShortcutWndJoypad.Shortcut" $ ( i + 1 ), nShortcutID );
			nShortcutID++;
		}
		break;
	case JOYSHORTCUT_Right:
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypadExpand.JoypadButtonBackTex", "L2UI_CH3.ShortcutWnd.joypad2_back_over3" );
		class'UIAPI_TEXTURECTRL'.static.SetAnchor( "ShortcutWnd.ShortcutWndJoypadExpand.JoypadButtonBackTex", "ShortcutWnd.ShortcutWndJoypadExpand", "TopLeft", "TopLeft", 288, 0 );
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypad.JoypadLButtonTex", "L2UI_ch3.Joypad.joypad_L" );
		class'UIAPI_TEXTURECTRL'.static.SetTexture( "ShortcutWnd.ShortcutWndJoypad.JoypadRButtonTex", "L2UI_ch3.Joypad.joypad_R_HOLD" );
		nShortcutID = CurrentShortcutPage * MAX_ShortcutPerPage + 8;
		for( i = 0; i < 4; ++i )
		{
			class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd.ShortcutWndJoypad.Shortcut" $ ( i + 1 ), nShortcutID );
			nShortcutID++;
		}
		break;
	}
}

function OnClickButton( string a_strID )
{
	switch( a_strID )
	{
	case "PrevBtn":
		OnPrevBtn();
		break;
	case "NextBtn":
		OnNextBtn();
		break;
	case "PrevBtn2":
		OnPrevBtn2();
		break;
	case "NextBtn2":
		OnNextBtn2();
		break;
	case "PrevBtn3":
		OnPrevBtn3();
		break;
	case "NextBtn3":
		OnNextBtn3();
		break;
	case "LockBtn":
		OnClickLockBtn();
		break;
	case "UnlockBtn":
		OnClickUnlockBtn();
		break;
	case "RotateBtn":
		OnRotateBtn();
		break;
	case "JoypadBtn":
		OnJoypadBtn();
		break;
	case "ExpandBtn":
		OnExpandBtn();
		break;
	case "ExpandButton":
		OnClickExpandShortcutButton();
		break;
	case "ReduceButton":
		OnClickExpandShortcutButton();
		break;
	//i??i??(10.02.25)
	case "PrevBtn4":
		OnPrevBtn4();
		break;
	case "NextBtn4":
		OnNextBtn4();
		break;
	case "PrevBtn5":
		OnPrevBtn5();
		break;
	case "NextBtn5":
		OnNextBtn5();
		break;
	case "PrevBtn6":
		OnPrevBtn6();
		break;
	case "NextBtn6":
		OnNextBtn6();
		break;

	//i??i??(10.05.07)
	case "TooltipMinBtn":
		OnMinBtn();
		break;
	case "TooltipMaxBtn":
		OnMaxBtn();
		break;
	case "btnOpenPanelH":
	case "btnOpenPanelV":
		OnClickOpenBtn();
		break;
	case "btnClosePanelH":
	case "btnClosePanelV":
		OnClickCloseBtn();
		break;
	case "btnOpenPanel1H":
	case "btnOpenPanel1V":
		OnClickOpenBtn1();
		break;
	case "btnClosePanel1H":
	case "btnClosePanel1V":
		OnClickCloseBtn1();
		break;
	case "btnInstanceH":
	case "btnInstanceV":
		AddChatInfoZone();	
		break;
	case "btnOlyH":
	case "btnOlyV":
		if (class'UIAPI_WINDOW'.static.IsShowWindow("OlympiadArenaListWnd"))
			class'UIAPI_WINDOW'.static.HideWindow("OlympiadArenaListWnd");
		else
			class'OlympiadAPI'.static.RequestOlympiadMatchList();
		break;
	}
}

function AddChatInfoZone()
{
	local Color ColorMsg;
	local ChatWnd script2;	// ??? ???? ???
	
	//script1 = PartyWnd( GetScript("PartyWnd") );
	script2 = ChatWnd( GetScript("ChatWnd") );	
	ColorMsg.R = 120;
	ColorMsg.G = 120;
	ColorMsg.B = 120;
	
	script2.SystemMsg.AddString( "> ---------- Instance Zone ---------- <", ColorMsg );
	ExecuteCommand("/instancezone");
//	test = GetTotalSceneTime();
//	AddSystemMessageString(string(int(test*1000)));
//	script2.SystemMsg.AddString( "> -------------------------------------- <", ColorMsg );
	//PartyChat.AddString( sInfo, ColorMsg );
	//ClanChat.AddString( sInfo, ColorMsg );
	//AllyChat.AddString( sInfo, ColorMsg );
}

function OnClickOpenBtn()
{
	OpenFlightShipWnd();
}

function OnClickCloseBtn()
{
	CloseFlightShipWnd();
}

function OnClickOpenBtn1()
{
	OpenFlightCtrlWnd();
}

function OnClickCloseBtn1()
{
	CloseFlightCtrlWnd();
}

function OpenFlightShipWnd()
{
	m_IsPanelOpened = true;
	SetOptionBool( "Game", "IsOpendFlightShipWnd", true );
	
	m_hFlightShipWnd.ShowWindow();
	
	ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.btnClosePanelH" );
	ShowWindow( "ShortcutWnd.ShortcutWndVertical.btnClosePanelV" );
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.btnOpenPanelH" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.btnOpenPanelV" );

}

function CloseFlightShipWnd()
{
	m_IsPanelOpened = false;
	SetOptionBool( "Game", "IsOpendFlightShipWnd", false );
	
	m_hFlightShipWnd.HideWindow();
	
	ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.btnOpenPanelH" );
	ShowWindow( "ShortcutWnd.ShortcutWndVertical.btnOpenPanelV" );
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.btnClosePanelH" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.btnClosePanelV" );
}

function OpenFlightCtrlWnd()
{
	m_IsPanelOpened1 = true;
	SetOptionBool( "Game", "IsOpendFlightShipWnd1", true );
	
	m_hFlightCtrlWnd.ShowWindow();
	
	if (GetEquippedBracelet()) class'UIAPI_WINDOW'.static.ShowWindow("TaliWnd");
	else class'UIAPI_WINDOW'.static.HideWindow("TaliWnd");
	
	ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.btnClosePanel1H" );
	ShowWindow( "ShortcutWnd.ShortcutWndVertical.btnClosePanel1V" );
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.btnOpenPanel1H" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.btnOpenPanel1V" );

}

function CloseFlightCtrlWnd()
{
	m_IsPanelOpened1 = false;
	SetOptionBool( "Game", "IsOpendFlightShipWnd1", false );
	
	m_hFlightCtrlWnd.HideWindow();
	class'UIAPI_WINDOW'.static.HideWindow("TaliWnd");
	
	ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.btnOpenPanel1H" );
	ShowWindow( "ShortcutWnd.ShortcutWndVertical.btnOpenPanel1V" );
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.btnClosePanel1H" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.btnClosePanel1V" );

}

function OnMinBtn()
{
	local Tooltip Script;
	
	HandleShortcutClear();
	ArrangeWnd();
	ExpandWnd();

	Script = Tooltip( GetScript( "Tooltip" ) );
	Script.setBoolSelect( true );

	// 2010.8.23 - winkey
	ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMaxBtn" );
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMinBtn" );
	ShowWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMaxBtn" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMinBtn" );
	
	SetOptionBool( "Game", "IsShortcutWndMinTooltip", false );
}

function OnMaxBtn()
{
	local Tooltip Script;
	
	HandleShortcutClear();
	ArrangeWnd();
	ExpandWnd();

	Script = Tooltip( GetScript( "Tooltip" ) );
	Script.setBoolSelect( false );

	// 2010.8.23 - winkey
	ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMinBtn" );
	HideWindow( "ShortcutWnd.ShortcutWndHorizontal.TooltipMaxBtn" );
	ShowWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMinBtn" );
	HideWindow( "ShortcutWnd.ShortcutWndVertical.TooltipMaxBtn" );
	
	SetOptionBool( "Game", "IsShortcutWndMinTooltip", true );
}

function OnPrevBtn()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage - 1;
	if( 0 > nNewPage )
		nNewPage = MAX_Page - 1;

	SetCurPage( nNewPage );
}

function OnPrevBtn2()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage2 - 1;
	if( 0 > nNewPage )
		nNewPage = MAX_Page - 1;

	SetCurPage2( nNewPage );
}

function OnPrevBtn3()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage3 - 1;
	if( 0 > nNewPage )
		nNewPage = MAX_Page - 1;

	SetCurPage3( nNewPage );
}

function OnNextBtn()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage + 1;
	if( MAX_Page <= nNewPage )
		nNewPage = 0;

	SetCurPage( nNewPage );
}

function OnNextBtn2()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage2 + 1;
	if( MAX_Page <= nNewPage )
		nNewPage = 0;

	SetCurPage2( nNewPage );
}

function OnNextBtn3()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage3 + 1;
	if( MAX_Page <= nNewPage )
		nNewPage = 0;

	SetCurPage3( nNewPage );
}


function OnPrevBtn4()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage4 - 1;
	if( 0 > nNewPage )
		nNewPage = MAX_Page - 1;

	SetCurPage4( nNewPage );
}

function OnNextBtn4()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage4 + 1;
	if( MAX_Page <= nNewPage )
		nNewPage = 0;

	SetCurPage4( nNewPage );
}

function OnPrevBtn5()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage5 - 1;
	if( 0 > nNewPage )
		nNewPage = MAX_Page - 1;

	SetCurPage5( nNewPage );
}

function OnNextBtn5()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage5 + 1;
	if( MAX_Page <= nNewPage )
		nNewPage = 0;

	SetCurPage5( nNewPage );
}

function OnPrevBtn6()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage6 - 1;
	if( 0 > nNewPage )
		nNewPage = MAX_Page - 1;

	SetCurPage6( nNewPage );
}

function OnNextBtn6()
{
	local int nNewPage;

	nNewPage = CurrentShortcutPage6 + 1;
	if( MAX_Page <= nNewPage )
		nNewPage = 0;

	SetCurPage6( nNewPage );
}

function OnClickLockBtn()
{
	UnLock();
}

function OnClickUnlockBtn()
{
	Lock();
}

function OnRotateBtn()
{
	SetVertical( !m_IsVertical );
	
	script_items.SetPosition();
	
	if( m_IsVertical )
	{
		class'UIAPI_WINDOW'.static.SetAnchor( "ShortcutWnd.ShortcutWndVertical", "ShortcutWnd.ShortcutWndHorizontal", "BottomRight", "BottomRight", 0, 0 );
		class'UIAPI_WINDOW'.static.ClearAnchor( "ShortcutWnd.ShortcutWndVertical" );
		class'UIAPI_WINDOW'.static.SetAnchor( "ShortcutWnd.ShortcutWndHorizontal", "ShortcutWnd.ShortcutWndVertical", "BottomRight", "BottomRight", 0, 0 );
	}
	else 
	{
		class'UIAPI_WINDOW'.static.SetAnchor( "ShortcutWnd.ShortcutWndHorizontal", "ShortcutWnd.ShortcutWndVertical", "BottomRight", "BottomRight", 0, 0 );                                
		class'UIAPI_WINDOW'.static.ClearAnchor( "ShortcutWnd.ShortcutWndHorizontal" );                                                                                                     
		class'UIAPI_WINDOW'.static.SetAnchor( "ShortcutWnd.ShortcutWndVertical", "ShortcutWnd.ShortcutWndHorizontal", "BottomRight", "BottomRight", 0, 0 );
	}
	
	//i??i??(10.02.25)
	if (m_IsExpand5 == true)
	{
		Expand1();
		Expand2();
		Expand3();
		Expand4();
		Expand5();
	}
	if (m_IsExpand4 == true)
	{
		Expand1();
		Expand2();
		Expand3();
		Expand4();
	}
	if(m_IsExpand3 == true)
	{
		Expand1();
		Expand2();
		Expand3();
	}
	if(m_IsExpand2 == true)
	{
		Expand1();
		Expand2();
	}
	if(m_IsExpand1 == true)
	{
		Expand1();
	}

	class'UIAPI_WINDOW'.static.SetFocus( "ShortcutWnd." $ m_ShortcutWndName );
}

function OnJoypadBtn()
{
	SetJoypad( !m_IsJoypad );
	class'UIAPI_WINDOW'.static.SetFocus( "ShortcutWnd." $ m_ShortcutWndName );
}

function OnExpandBtn()
{
	SetJoypadExpand( !m_IsJoypadExpand );
	class'UIAPI_WINDOW'.static.SetFocus( "ShortcutWnd." $ m_ShortcutWndName );
}

function SetCurPage( int a_nCurPage )
{
	if( 0 > a_nCurPage || MAX_Page <= a_nCurPage )
		return;

	// Debug("SetCurPage: " $ a_nCurPage);
	//Set Current ShortcutKey(F1,F2,F3...) ShortcutWnd Num
	//e?¡§i¢Ò?i?¢´e?¡± i©÷¡ìe©÷?i¡×¢¬i?i¡í¡¤i¡Æ¨öe¡×¨« i¡Æ¢¬i¢®¡Æi??e?¢æe¢®©«..
	class'ShortcutAPI'.static.SetShortcutPage( a_nCurPage );
	
	//->EV_ShortcutPageUpdate e¡Æ¢æ e??e¨ùi?¡§e?¢´.
}

function SetCurPage2( int a_nCurPage )
{
	local int i;
	local int nShortcutID;
	
	if( 0 > a_nCurPage || MAX_Page <= a_nCurPage )
		return;
		
	CurrentShortcutPage2 = a_nCurPage;
	class'UIAPI_TEXTBOX'.static.SetText( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1" $ ".PageNumTextBox", string( CurrentShortcutPage2 + 1 ) );
	nShortcutID = CurrentShortcutPage2 * MAX_ShortcutPerPage;
	for( i = 0; i < MAX_ShortcutPerPage; ++i )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1" $".Shortcut" $ ( i + 1 ), nShortcutID );
		nShortcutID++;
	}
}

function SetCurPage3( int a_nCurPage )
{
	local int i;
	local int nShortcutID;
	
	if( 0 > a_nCurPage || MAX_Page <= a_nCurPage )
		return;
		
	CurrentShortcutPage3 = a_nCurPage;
	class'UIAPI_TEXTBOX'.static.SetText( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_2" $ ".PageNumTextBox", string( CurrentShortcutPage3 + 1 ) );	
	nShortcutID = CurrentShortcutPage3 * MAX_ShortcutPerPage;
	for( i = 0; i < MAX_ShortcutPerPage; ++i )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_2" $ ".Shortcut" $ ( i + 1 ), nShortcutID );
		nShortcutID++;
	}
}

//i??i??(10.02.25)
function SetCurPage4( int a_nCurPage )
{
	local int i;
	local int nShortcutID;
	
	if( 0 > a_nCurPage || MAX_Page <= a_nCurPage )
		return;
		
	CurrentShortcutPage4 = a_nCurPage;
	class'UIAPI_TEXTBOX'.static.SetText( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_3" $ ".PageNumTextBox", string( CurrentShortcutPage4 + 1 ) );	
	nShortcutID = CurrentShortcutPage4 * MAX_ShortcutPerPage;
	for( i = 0; i < MAX_ShortcutPerPage; ++i )
	{
		// debug( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_3" $ ".Shortcut" $ ( i + 1 ) @ nShortcutID );

		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_3" $ ".Shortcut" $ ( i + 1 ), nShortcutID );
		nShortcutID++;
	}
}

function SetCurPage5( int a_nCurPage )
{
	local int i;
	local int nShortcutID;
	
	if( 0 > a_nCurPage || MAX_Page <= a_nCurPage )
		return;
		
	CurrentShortcutPage5 = a_nCurPage;
	class'UIAPI_TEXTBOX'.static.SetText( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_4" $ ".PageNumTextBox", string( CurrentShortcutPage5 + 1 ) );	
	nShortcutID = CurrentShortcutPage5 * MAX_ShortcutPerPage;
	for( i = 0; i < MAX_ShortcutPerPage; ++i )
	{
		// debug( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_3" $ ".Shortcut" $ ( i + 1 ) @ nShortcutID );

		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_4" $ ".Shortcut" $ ( i + 1 ), nShortcutID );
		nShortcutID++;
	}
}

function SetCurPage6( int a_nCurPage )
{
	local int i;
	local int nShortcutID;
	
	if( 0 > a_nCurPage || MAX_Page <= a_nCurPage )
		return;
		
	CurrentShortcutPage6 = a_nCurPage;
	class'UIAPI_TEXTBOX'.static.SetText( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_5" $ ".PageNumTextBox", string( CurrentShortcutPage6 + 1 ) );	
	nShortcutID = CurrentShortcutPage6 * MAX_ShortcutPerPage;
	for( i = 0; i < MAX_ShortcutPerPage; ++i )
	{
		// debug( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_3" $ ".Shortcut" $ ( i + 1 ) @ nShortcutID );

		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "ShortcutWnd." $ m_ShortcutWndName $ "." $ m_ShortcutWndName $ "_1." $ m_ShortcutWndName $"_5" $ ".Shortcut" $ ( i + 1 ), nShortcutID );
		nShortcutID++;
	}
}

function bool IsShortcutIDInCurPage( int PageNum, int a_nShortcutID )
{
	if( PageNum * MAX_ShortcutPerPage > a_nShortcutID )
		return false;
	if( ( PageNum + 1 ) * MAX_ShortcutPerPage <= a_nShortcutID )
		return false;
	return true;
}

function Lock()
{
	m_IsLocked = true;
	SetOptionBool( "Game", "IsLockShortcutWnd", true );

	//if( IsShowWindow( "ShortcutWnd" ) )
	//{
		
		ShowWindow( "ShortcutWnd.ShortcutWndVertical.LockBtn" );
		ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.LockBtn" );
		HideWindow( "ShortcutWnd.ShortcutWndVertical.UnlockBtn" );
		HideWindow( "ShortcutWnd.ShortcutWndHorizontal.UnlockBtn" );
	//}
}

function UnLock()
{
	m_IsLocked = false;
	SetOptionBool( "Game", "IsLockShortcutWnd", false );

	//if( IsShowWindow( "ShortcutWnd" ) )
	//{
		ShowWindow( "ShortcutWnd.ShortcutWndVertical.UnlockBtn" );
		ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.UnlockBtn" );
		HideWindow( "ShortcutWnd.ShortcutWndVertical.LockBtn" );
		HideWindow( "ShortcutWnd.ShortcutWndHorizontal.LockBtn" );
	//}
}

function SetVertical( bool a_IsVertical )
{
	m_IsVertical = a_IsVertical;
	SetOptionBool( "Game", "IsShortcutWndVertical", m_IsVertical );

	ArrangeWnd();
	ExpandWnd();
}

function SetJoypad( bool a_IsJoypad )
{
	m_IsJoypad = a_IsJoypad;

	ArrangeWnd();
}

function SetJoypadExpand( bool a_IsJoypadExpand )
{
	m_IsJoypadExpand = a_IsJoypadExpand;

	if( m_IsJoypadExpand )
	{
		class'UIAPI_WINDOW'.static.SetAnchor( "ShortcutWnd.ShortcutWndJoypadExpand", "ShortcutWnd.ShortcutWndJoypad", "TopLeft", "TopLeft", 0, 0 );
		class'UIAPI_WINDOW'.static.ClearAnchor( "ShortcutWnd.ShortcutWndJoypadExpand" );
	}
	else
	{
		class'UIAPI_WINDOW'.static.SetAnchor( "ShortcutWnd.ShortcutWndJoypad", "ShortcutWnd.ShortcutWndJoypadExpand", "TopLeft", "TopLeft", 0, 0 );
		class'UIAPI_WINDOW'.static.ClearAnchor( "ShortcutWnd.ShortcutWndJoypad" );
	}

	ArrangeWnd();
}

function ArrangeWnd()
{
	local Rect WindowRect;

	if( m_IsJoypad )
	{
		HideWindow( "ShortcutWnd.ShortcutWndVertical" );
		HideWindow( "ShortcutWnd.ShortcutWndHorizontal" );
		if( m_IsJoypadExpand )
		{
			HideWindow( "ShortcutWnd.ShortcutWndJoypad" );
			ShowWindow( "ShortcutWnd.ShortcutWndJoypadExpand" );

			m_ShortcutWndName = "ShortcutWndJoypadExpand";
		}
		else
		{
			HideWindow( "ShortcutWnd.ShortcutWndJoypadExpand" );
			ShowWindow( "ShortcutWnd.ShortcutWndJoypad" );

			m_ShortcutWndName = "ShortcutWndJoypad";
		}
	}
	else
	{
		HideWindow( "ShortcutWnd.ShortcutWndJoypadExpand" );
		HideWindow( "ShortcutWnd.ShortcutWndJoypad" );
		if( m_IsVertical )
		{
			m_ShortcutWndName = "ShortcutWndVertical";
			WindowRect = class'UIAPI_WINDOW'.static.GetRect( "ShortcutWnd.ShortcutWndVertical" );
			if( WindowRect.nY < 0 )
				class'UIAPI_WINDOW'.static.MoveTo( "ShortcutWnd.ShortcutWndVertical", WindowRect.nX, 0 );
			HideWindow( "ShortcutWnd.ShortcutWndHorizontal" );
			ShowWindow( "ShortcutWnd.ShortcutWndVertical" );
		}
		else
		{
			m_ShortcutWndName = "ShortcutWndHorizontal";
			WindowRect = class'UIAPI_WINDOW'.static.GetRect( "ShortcutWnd.ShortcutWndHorizontal" );
			if( WindowRect.nX < 0 )
				class'UIAPI_WINDOW'.static.MoveTo( "ShortcutWnd.ShortcutWndHorizontal", 0, WindowRect.nY );
			HideWindow( "ShortcutWnd.ShortcutWndVertical" );
			ShowWindow( "ShortcutWnd.ShortcutWndHorizontal" );
		}

		if( m_IsJoypadOn )
			ShowWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".JoypadBtn" );
		else
			HideWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".JoypadBtn" );
	}
	
	m_IsLocked = GetOptionBool( "Game", "IsLockShortcutWnd" );	
	if( m_IsLocked )
		Lock();
	else
		UnLock();

	SetCurPage( CurrentShortcutPage );
	SetCurPage2( CurrentShortcutPage2 );
	SetCurPage3( CurrentShortcutPage3 );
	SetCurPage4( CurrentShortcutPage4 );
	SetCurPage5( CurrentShortcutPage5 );
	SetCurPage6( CurrentShortcutPage6 );
	
	if(m_isExpand1 || m_isExpand2 || m_IsExpand3 || m_IsExpand4 || m_IsExpand5)
	{
		m_IsShortcutExpand = false;
		HandleExpandButton();
	}
	else
	{
		m_IsShortcutExpand = true;
		HandleExpandButton();
	}
}

function ExpandWnd()
{
	//i??i??(10.02.25)
	if( m_IsExpand1 || m_IsExpand2  || m_IsExpand3 || m_IsExpand4 || m_IsExpand5 )
	{
		//debug( m_IsExpand1 @ "&&&&&" @ m_IsExpand2 @ "&&&&&" @ m_IsExpand3 );
		m_IsShortcutExpand = false;
		if (m_IsExpand5)
		{
			Expand5();
		}	
		if (m_IsExpand4)
		{
			Expand4();
		}	
		if (m_IsExpand3)
		{
			Expand3();
		}	
		if (m_IsExpand2)
		{
			Expand2();
		}	
		if (m_IsExpand1)
		{
			Expand1();
		}
			
	}
	else
	{
		m_IsShortcutExpand = true;
		Reduce();
	}
}

function Expand1()
{
	m_IsShortcutExpand = true;
	m_IsExpand1 = true;
	SetOptionBool( "Game", "Is1ExpandShortcutWnd", m_IsExpand1 );
	
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndVertical_1");
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndHorizontal_1");
	
	HandleExpandButton();
}

function Expand2()
{
	m_IsShortcutExpand = true;	
	m_IsExpand2 = true;
	SetOptionBool( "Game", "Is2ExpandShortcutWnd", m_IsExpand2 );
	
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndVertical_2");
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndHorizontal_2");
	
	HandleExpandButton();
}

//i??i??(10.02.25)
function Expand3()
{
	m_IsShortcutExpand = true;	
	m_IsExpand3 = true;
	SetOptionBool( "Game", "Is3ExpandShortcutWnd", m_IsExpand3 );
	
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndVertical_3");
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndHorizontal_3");
	
	HandleExpandButton();
}

function Expand4()
{
	m_IsShortcutExpand = true;	
	m_IsExpand4 = true;
	SetOptionBool( "Game", "Is4ExpandShortcutWnd", m_IsExpand4 );
	
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndVertical_4");
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndHorizontal_4");
	
	HandleExpandButton();
}

function Expand5()
{
	m_IsShortcutExpand = true;	
	m_IsExpand5 = true;
	SetOptionBool( "Game", "Is5ExpandShortcutWnd", m_IsExpand5 );
	
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndVertical_5");
	class'UIAPI_WINDOW'.static.ShowWindow("ShortcutWnd.ShortcutWndHorizontal_5");
	
	HandleExpandButton();
}

function Reduce()
{
	m_IsShortcutExpand = true;
	m_IsExpand1 = false;
	m_IsExpand2 = false;
	SetOptionBool( "Game", "Is1ExpandShortcutWnd", m_IsExpand1 );
	SetOptionBool( "Game", "Is2ExpandShortcutWnd", m_IsExpand2 );

	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndVertical_1");
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndVertical_2");
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndHorizontal_1");
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndHorizontal_2");
	
	//i??i??(10.02.25)
	m_IsExpand3 = false;
	SetOptionBool( "Game", "Is3ExpandShortcutWnd", m_IsExpand3 );
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndVertical_3");
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndHorizontal_3");

	m_IsExpand4 = false;
	SetOptionBool( "Game", "Is4ExpandShortcutWnd", m_IsExpand4 );
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndVertical_4");
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndHorizontal_4");

	m_IsExpand5 = false;
	SetOptionBool( "Game", "Is5ExpandShortcutWnd", m_IsExpand5 );
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndVertical_5");
	class'UIAPI_WINDOW'.static.HideWindow("ShortcutWnd.ShortcutWndHorizontal_5");

	HandleExpandButton();
}

function OnClickExpandShortcutButton()
{
	//i??i??(10.02.25)
	// debug( "m_IsExpand3------->" @  m_IsExpand3 );
	// debug( "m_IsExpand2------->" @  m_IsExpand2 );
	// debug( "m_IsExpand1------->" @  m_IsExpand1 );

	if (!m_IsVertical)
	{
		if (m_IsExpand5)
		{
			Reduce();
			AnchorItems("ShortcutWnd.ShortcutWndHorizontal");
		}
		else if (m_IsExpand4)
		{
			Expand5();
			AnchorItems("ShortcutWnd.ShortcutWndHorizontal_5");
		}	
		else if (m_IsExpand3)
		{
			Expand4();
			AnchorItems("ShortcutWnd.ShortcutWndHorizontal_4");
		}	
		else if (m_IsExpand2)
		{
			Expand3();
			AnchorItems("ShortcutWnd.ShortcutWndHorizontal_3");
		}	
		else if (m_IsExpand1)
		{
			Expand2();
			AnchorItems("ShortcutWnd.ShortcutWndHorizontal_2");
		}	
		else
		{
			Expand1();
			AnchorItems("ShortcutWnd.ShortcutWndHorizontal_1");
		}
	}
	else
	{
		if (m_IsExpand5)
		{
			Reduce();
			AnchorItems("ShortcutWnd.ShortcutWndVertical");
		}
		else if (m_IsExpand4)
		{
			Expand5();
			AnchorItems("ShortcutWnd.ShortcutWndVertical_5");
		}	
		else if (m_IsExpand3)
		{
			Expand4();
			AnchorItems("ShortcutWnd.ShortcutWndVertical_4");
		}	
		else if (m_IsExpand2)
		{
			Expand3();
			AnchorItems("ShortcutWnd.ShortcutWndVertical_3");
		}	
		else if (m_IsExpand1)
		{
			Expand2();
			AnchorItems("ShortcutWnd.ShortcutWndVertical_2");
		}	
		else
		{
			Expand1();
			AnchorItems("ShortcutWnd.ShortcutWndVertical_1");
		}
	}
}

function bool IsOnVisiblePanel(int idx) {
	if (idx >= MAX_ShortcutPerPage * CurrentShortcutPage &&
		idx < MAX_ShortcutPerPage * (CurrentShortcutPage + 1))
		return true;
	if (m_IsExpand1 && idx >= MAX_ShortcutPerPage * CurrentShortcutPage2 &&
		idx < MAX_ShortcutPerPage * (CurrentShortcutPage2 + 1))
		return true;
	if (m_IsExpand2 && idx >= MAX_ShortcutPerPage * CurrentShortcutPage3 &&
		idx < MAX_ShortcutPerPage * (CurrentShortcutPage3 + 1))
		return true;
	if (m_IsExpand3 && idx >= MAX_ShortcutPerPage * CurrentShortcutPage4 &&
		idx < MAX_ShortcutPerPage * (CurrentShortcutPage4 + 1))
		return true;
	return false;
}

function ExecuteShortcutCommandBySlot(string param)
{
	local int slot;
	ParseInt(param, "Slot", slot);
	//Log("CurrentShortcutPage 1 " $ CurrentShortcutPage[0] $ ", 2 " $ CurrentShortcutPage[1] $ ", 3 " $ CurrentShortcutPage[2]);
	
	if(Me.isShowwindow())		// i¡Æ¨öi¢¥ e©ø¢¥i?¡þi¡×? e?¨«e¡×¨« i??i?¢¶i??e?e¢® i©÷?e|¡þ.
	{
		if( slot >=0 && slot < MAX_ShortcutPerPage )			// bottom
		{
			class'ShortcutAPI'.static.ExecuteShortcutBySlot(CurrentShortcutPage*MAX_ShortcutPerPage + slot);
		}
		else if( slot >= MAX_ShortcutPerPage && slot < MAX_ShortcutPerPage*2 )		// middle
		{
			//debug ("i?¡þe¢®?i??i¢¥i¡×¢æ2");
			class'ShortcutAPI'.static.ExecuteShortcutBySlot(CurrentShortcutPage2*MAX_ShortcutPerPage + slot - MAX_ShortcutPerPage);
		}
		else if( slot >= MAX_ShortcutPerPage*2 && slot < MAX_ShortcutPerPage*3 )		// last
		{
			//debug ("i?¡þe¢®?i??i¢¥i¡×¢æ3");
			class'ShortcutAPI'.static.ExecuteShortcutBySlot(CurrentShortcutPage3*MAX_ShortcutPerPage + slot - MAX_ShortcutPerPage2);
		}
		else if( slot >= MAX_ShortcutPerPage*3 && slot < MAX_ShortcutPerPage*4 ) {
			class'ShortcutAPI'.static.ExecuteShortcutBySlot(CurrentShortcutPage4*MAX_ShortcutPerPage + slot - MAX_ShortcutPerPage3);
		}
		else if( slot >= MAX_ShortcutPerPage*4 && slot < MAX_ShortcutPerPage*5 ) {
			class'ShortcutAPI'.static.ExecuteShortcutBySlot(CurrentShortcutPage5*MAX_ShortcutPerPage + slot - MAX_ShortcutPerPage4);
		}
		else if( slot >= MAX_ShortcutPerPage*5 && slot < MAX_ShortcutPerPage*6 ) {
			class'ShortcutAPI'.static.ExecuteShortcutBySlot(CurrentShortcutPage6*MAX_ShortcutPerPage + slot - MAX_ShortcutPerPage5);
		}
	}
}

function HandleExpandButton()
{
///	if( m_IsShortcutExpand )
//	{
//		ShowWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".ExpandButton" );
//		HideWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".ReduceButton" );
//	}
//	else
//	{
//		HideWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".ExpandButton" );
//		ShowWindow( "ShortcutWnd." $ m_ShortcutWndName $ ".ReduceButton" );
//	}
	
	if (m_IsExpand5)
	{
		ShowWindow( "ShortcutWnd.ShortcutWndVertical.ReduceButton" );
		ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.ReduceButton" );
		HideWindow( "ShortcutWnd.ShortcutWndVertical.ExpandButton" );
		HideWindow( "ShortcutWnd.ShortcutWndHorizontal.ExpandButton" );
	}
	else
	{
		HideWindow( "ShortcutWnd.ShortcutWndVertical.ReduceButton" );
		HideWindow( "ShortcutWnd.ShortcutWndHorizontal.ReduceButton" );
		ShowWindow( "ShortcutWnd.ShortcutWndVertical.ExpandButton" );
		ShowWindow( "ShortcutWnd.ShortcutWndHorizontal.ExpandButton" );	
	}
}


function AnchorItems(string window)
{
	local bool isVert;	
	isVert = GetOptionBool( "Game", "IsShortcutWndVertical" );
//	if (!isVert) script_items.Me.SetAnchor(window, "TopLeft", "TopLeft", x+36, y+10);
//	else script_items.Me.SetAnchor(window, "TopLeft", "TopLeft", x+10, y+40);
	if (!isVert) 
		script_items.Me.SetAnchor(window $ ".bakdrop6", "TopRight", "TopCenter", 0, -38);
	else 
		script_items.Me.SetAnchor(window $ ".bakdrop6", "BottomLeft", "CenterLeft", -38, 0);
	
}

defaultproperties
{
}
