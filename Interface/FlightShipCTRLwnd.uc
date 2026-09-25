class FlightShipCtrlWnd extends UIScriptEx;

// 디파인
const MAX_ShortcutPerPage = 12;	// 12칸의 숏컷을 지원한다. 
const FSShortcutPage = 11;		// 비행정은 11번 페이지를 사용한다. 
const Relative_Altitude = 4000;	// 실 좌표계에 +a 하는 값

const SelectTex_X = -5;
const SelectTex_Y = -5;

// 전역 변수
var WindowHandle Me;
var WindowHandle ShortcutWnd;

var	CheckBoxHandle 	Chk_EnterChatting;	//shortcut assign wnd의 엔터채팅 모드 체크박스.
var	TextBoxHandle	AltitudeTxt;		//고도 텍스트박스
var	TextureHandle	SelectTex;			// 선택된 아이템을 알려주는 텍스쳐

var	ButtonHandle	UpButton;			// 고도 상승
var	ButtonHandle	DownButton;		// 고도 하강


var	ButtonHandle	LockBtn;			// 잠금 버튼
var	ButtonHandle	UnlockBtn;			// 잠금 해제 버튼
var	ButtonHandle	JoypadBtn;			// 조이패드

var 	EditBoxHandle ChatEditBox;			// 채팅 에디트 박스

var  	ShortcutWnd 	scriptShortcutWnd;		

var	int i;							//루프 돌릴때 사용하는 변수

var	bool 		preEnterChattingOption;

var 	bool m_IsLocked;	// 숏컷 잠금 변수

var	bool	m_preDriver;	// 이전 상태에서 드라이버였는지를 저장한다.

var	bool isNowActiveFlightShipShortcut;	// 현재 조종모드인지를 저장한다. 향상된 세이더같이 게임중 로딩이 나올 수 있으므로.

var	int preSlot;			// 이전에 활성화된 슬롯을 저장해둔다.

// 이벤트 등록
function OnRegisterEvent()
{
	RegisterEvent( EV_AirShipState );
	RegisterEvent( EV_AirShipAltitude);
	
	RegisterEvent( EV_ShortcutCommandSlot );	//숏컷 이벤트
	RegisterEvent( EV_ReserveShortCut);		// 예약된 숏컷 이벤트	
	
	RegisterEvent( EV_ShortcutPageUpdate );
	RegisterEvent( EV_ShortcutUpdate );
	RegisterEvent( EV_ShortcutClear );	
}

function OnLoad()
{
	if(CREATE_ON_DEMAND==0)
		OnRegisterEvent();
	
	if(CREATE_ON_DEMAND==0)		// 과거의 핸들 받기
	{
		Me = GetHandle( "FlightShipCtrlWnd" );
		ShortcutWnd = GetHandle( "ShortcutWnd" );
		Chk_EnterChatting = CheckBoxHandle ( GetHandle( "OptionWnd.ShortcutTab.OptionCheckboxGroup.Chk_EnterChatting"));
		AltitudeTxt = TextBoxHandle ( GetHandle ( "FlightShipCtrlWnd.AltitudeTxt"));
		SelectTex = TextureHandle ( GetHandle( "FlightShipCtrlWnd.FlightShortCut.SelectTex"));
		
		UpButton = ButtonHandle ( GetHandle( "FlightShipCtrlWnd.FlightSteerWnd.UpButton"));
		DownButton = ButtonHandle ( GetHandle( "FlightShipCtrlWnd.FlightSteerWnd.DownButton"));
		LockBtn = ButtonHandle ( GetHandle( "FlightShipCtrlWnd.FlightShortCut.LockBtn"));
		UnlockBtn = ButtonHandle ( GetHandle( "FlightShipCtrlWnd.FlightShortCut.UnlockBtn"));
		JoypadBtn = ButtonHandle ( GetHandle( "FlightShipCtrlWnd.FlightShortCut.JoypadBtn"));
		
		ChatEditBox = EditBoxHandle( GetHandle("ChatWnd.ChatEditBox" ));
		
	}
	else	// 새로운 핸들 받기!!
	{
		Me = GetWindowHandle( "FlightShipCtrlWnd" );
		ShortcutWnd = GetWindowHandle( "ShortcutWnd" );
		Chk_EnterChatting = GetCheckBoxHandle( "OptionWnd.ShortcutTab.OptionCheckboxGroup.Chk_EnterChatting");
		AltitudeTxt = GetTextBoxHandle( "FlightShipCtrlWnd.AltitudeTxt");
		SelectTex = GetTextureHandle( "FlightShipCtrlWnd.FlightShortCut.SelectTex");
		
		UpButton = GetButtonHandle( "FlightShipCtrlWnd.FlightSteerWnd.UpButton");
		DownButton = GetButtonHandle ( "FlightShipCtrlWnd.FlightSteerWnd.DownButton");
		LockBtn = GetButtonHandle ( "FlightShipCtrlWnd.FlightShortCut.LockBtn");
		UnlockBtn = GetButtonHandle ( "FlightShipCtrlWnd.FlightShortCut.UnlockBtn");
		JoypadBtn = GetButtonHandle ( "FlightShipCtrlWnd.FlightShortCut.JoypadBtn");	
		
		ChatEditBox = GetEditBoxHandle( "ChatWnd.ChatEditBox" );
	}
	
	scriptShortcutWnd = ShortcutWnd( GetScript("ShortcutWnd") );	
	isNowActiveFlightShipShortcut = false;
	m_preDriver = false;
	preSlot = -1;
	JoypadBtn.HideWindow();	
	updateLockButton();	// 잠금 상태를 업데이트 한다. 
	ShortCutUpdateAll();
}

function OnEnterState( name a_PreStateName )
{
	if(isNowActiveFlightShipShortcut)	// 조종모드였었다면
	{
		if( a_PreStateName == 'ShaderBuildState')
		{
			if(!Me.isShowwindow()) Me.ShowWindow();					//전용 숏컷으로 변경
			if(ShortcutWnd.isShowwindow()) ShortcutWnd.HideWindow();
		}
	}
}

function OnExitState( name a_NextStateName )
{
	if( a_NextStateName != 'ShaderBuildState')	// 쉐이더 상테로 나갈 경우가 아니라면,  체크박스의 디스에이블을 풀어준다. 
	{
		Chk_EnterChatting.EnableWindow();	// 디스에이블 해제		
	}
}

function updateLockButton()
{
	m_IsLocked = GetOptionBool( "Game", "IsLockShortcutWnd" );
	if(m_IsLocked)
	{
		if(!LockBtn.isShowwindow()) LockBtn.ShowWindow();
		if(UnlockBtn.isShowwindow()) UnlockBtn.HideWindow();
	}
	else
	{
		if(LockBtn.isShowwindow()) LockBtn.HideWindow();
		if(!UnlockBtn.isShowwindow()) UnlockBtn.ShowWindow();
	}
	scriptShortcutWnd.ArrangeWnd();
}


// 이벤트 처리
function OnEvent( int a_EventID, String a_Param )
{
	switch( a_EventID )
	{
	case EV_AirShipState:
		OnAirShipState( a_Param );
		break;
	case EV_AirShipAltitude:
		OnAirShipAltitude( a_Param);
		break;
	case EV_ShortcutCommandSlot:
		ExecuteShortcutCommandBySlot(a_Param);	// 단축키 실행 이벤트
		break;
	case EV_ReserveShortCut:
		OnReserveShortCut( a_Param );
		break;
	case EV_ShortcutUpdate:
		HandleShortcutUpdate( a_Param );
		break;
	case EV_ShortcutPageUpdate:	// 페이지가 없기 때문에 전체를 업데이트 한다.
		ShortCutUpdateAll();
		break;
	case EV_ShortcutClear:
		HandleShortcutClear();
		updateLockButton();	// 잠금 상태를 업데이트 한다. 			
		break;
	default:
		break;
	}
}

function OnAirShipState( string a_Param )
{
	local int VehicleID;
	local int IsDriver;
	
	ParseInt( a_Param, "VehicleID", VehicleID );	// 비행정 위의 비행정 고도 및 HP 연료 등 UI는 RadarMapWnd.uc에서 처리하도록 한다. 
	ParseInt( a_Param, "IsDriver", IsDriver );
	
	debug("VehicleID = " $ VehicleID $" IsDriver = " $ IsDriver);
	
	if( IsDriver > 0 )	//조종사 단축키 모드
	{
		if(VehicleID > 0)	// 조종 모드 보이기, 해제에서는 항상 비행정 아이디가 존재하여야 한다. 
		{		
			preEnterChattingOption = GetOptionBool("Game", "EnterChatting");		//기존 엔터채팅 옵션을 저장해둔다. 		
			SetOptionBool( "Game", "EnterChatting", true );	//강제 엔터 채팅
			Chk_EnterChatting.SetCheck(true);
			Chk_EnterChatting.DisableWindow();			// 디스에이블
			
			class'ShortcutAPI'.static.ActivateGroup("FlightStateShortcut");		//숏컷 그룹 지정	
			updateLockButton();	// 잠금 상태를 업데이트 한다. 			
			if(!Me.isShowwindow()) 
			{
				Me.ShowWindow();					//전용 숏컷으로 변경
				ShortcutWnd.HideWindow();
			}
			
			ChatEditBox.ReleaseFocus();
			
			isNowActiveFlightShipShortcut = true;
			m_preDriver = true;
		}
	}
	else	// 조종사 해제 isDriver == 0
	{
		if(VehicleID > 0 && m_preDriver == true)		// 이전 상태가 조종모드였을때만 조종을 해제한다.
		{		
			Chk_EnterChatting.EnableWindow();	// 디스에이블 해제
			class'ShortcutAPI'.static.DeactivateGroup("FlightStateShortcut"); 	// 숏컷 그룹 해제		
			
			SetOptionBool( "Game", "EnterChatting", preEnterChattingOption );	//백업해둔 엔터채팅 옵션을 다시 넣어준다.
			if(preEnterChattingOption)	// 변신전 백업 상태에 따라 엔터 채팅을 활성화 해준다.			
			{			
				class'ShortcutAPI'.static.ActivateGroup("TempStateShortcut");
			}		
			Chk_EnterChatting.SetCheck(preEnterChattingOption);		
			
			if(Me.isShowwindow())	
			{
				Me.HideWindow();					// 원래 숏컷으로 돌려놓음
				ShortcutWnd.ShowWindow();
			}
			
			isNowActiveFlightShipShortcut = false;			
			ChatEditBox.ReleaseFocus();
		}
	}
}

function OnAirShipAltitude( string a_Param )
{
	local int m_nZ;
	
	ParseInt( a_Param, "Z" , m_nZ);
	
	AltitudeTxt.SetText(string(m_nZ + 4000));	// 고도를 업데이트 해준다. 
	
}

function ShortCutUpdateAll()
{
	local int nShortcutID;
	
	nShortcutID = MAX_ShortcutPerPage * FSShortcutPage;
	
	for( i = 0; i < MAX_ShortcutPerPage; ++i )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "FlightShipCtrlWnd.FlightShortCut.Shortcut" $ ( i + 1 ), nShortcutID );
		nShortcutID++;
	}
}

// 숏컷 업데이트
function HandleShortcutUpdate(string param)
{
	local int nShortcutID;
	local int nShortcutNum;
	
	ParseInt(param, "ShortcutID", nShortcutID);
	nShortcutNum = ( nShortcutID - MAX_ShortcutPerPage * FSShortcutPage) + 1;
	
	debug(" ----------fs------------- id : " $ nShortcutID $ " nShortcutNum " $ nShortcutNum );
	
	if((nShortcutNum > 0 ) && ( nShortcutNum  < MAX_ShortcutPerPage + 1 ))	// 비행숏컷일 경우에만  업데이트
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.UpdateShortcut( "FlightShipCtrlWnd.FlightShortCut.Shortcut" $ nShortcutNum, nShortcutID );
	}
}

 //숏컷 클리어
function HandleShortcutClear()
{		
	for( i = 0; i < MAX_ShortcutPerPage; ++i )
	{
		class'UIAPI_SHORTCUTITEMWINDOW'.static.clear( "FlightShipCtrlWnd.FlightShortCut.Shortcut" $ ( i + 1 ) );
	}
}

function ExecuteShortcutCommandBySlot( string a_Param )
{
	local int slot;
	//local int slotFromOne;
	ParseInt(a_Param, "Slot", slot);
	
	// 132번부터 143번까지의 슬롯이 들어오면 실행해준다. 
	if( (slot >= MAX_ShortcutPerPage * FSShortcutPage) && ( slot < MAX_ShortcutPerPage * (FSShortcutPage + 1)))
	{	
		//slotFromOne = slot - MAX_ShortcutPerPage * FSShortcutPage + 1;
		class'ShortcutAPI'.static.ExecuteShortcutBySlot(slot);
		//SelectTex.SetAnchor( "FlightShipCtrlWnd.FlightShortCut.F" $ slotFromOne $ "Tex" , "TopLeft", "TopLeft", SelectTex_X, SelectTex_Y );
	}
}

function OnReserveShortCut( string a_Param )
{
	local int slot;
	local int slotFromOne;
	ParseInt(a_Param, "Slot", slot);
	
	// 132번부터 143번까지의 슬롯이 들어오면 광을 내준다.
	if( (slot >= MAX_ShortcutPerPage * FSShortcutPage) && ( slot < MAX_ShortcutPerPage * (FSShortcutPage + 1)))
	{	
		slotFromOne = slot - MAX_ShortcutPerPage * FSShortcutPage + 1;
		SelectTex.SetAnchor( "FlightShipCtrlWnd.FlightShortCut.F" $ slotFromOne $ "Tex" , "TopLeft", "TopLeft", SelectTex_X, SelectTex_Y );
		
		if( preSlot == slotFromOne )	// 이미 선택된 상태에서 한번 더 누르면 실행된다.
		{
			class'ShortcutAPI'.static.ExecuteShortcutBySlot(slot);
		}
		else
		{
			preSlot = slotFromOne;
		}
	}
}

// 고도 상승, 하강 버튼
function OnClickButton( String strID )
{
	switch( strID )
	{
	case "UpButton":
		Class'VehicleAPI'.static.AirShipMoveUp();	//고도 상승
		break;
	case "DownButton":
		Class'VehicleAPI'.static.AirShipMoveDown(); // 고도 하강
		break;
	case "LockBtn":
		m_IsLocked = false;
		SetOptionBool( "Game", "IsLockShortcutWnd", false );
		updateLockButton();
		break;
	case "UnlockBtn":
		m_IsLocked = true;
		SetOptionBool( "Game", "IsLockShortcutWnd", true );
		updateLockButton();
		break;
	}
}
defaultproperties
{
}
