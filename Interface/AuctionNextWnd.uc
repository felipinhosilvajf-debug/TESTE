class AuctionNextWnd extends UICommonAPI;

// 각종 핸들 받아오기.
var WindowHandle Me;
var ButtonHandle BtnAuctionWndShowNext;
var ButtonHandle BtnClose;
var ItemWindowHandle AuctionItem;
var TextBoxHandle txtItemDesc;
var TextBoxHandle txtItemName;
var WindowHandle areaScroll;
var TextBoxHandle NextAuctionTime;


function OnRegisterEvent()
{
	// 이벤트 등록 
	RegisterEvent( EV_ITEM_AUCTION_NEXT_INFO );
}

function OnLoad()
{
	Initialize();
}

function OnShow()
{
	BtnAuctionWndShowNext.DisableWindow();
}

function Initialize()
{
	Me = GetWindowHandle( "NextAuctionDrawerWnd" );
	BtnAuctionWndShowNext = GetButtonHandle( "AuctionWnd.BtnNextAuction" );
	BtnClose = GetButtonHandle( "NextAuctionDrawerWnd.BtnClose" );
	AuctionItem = GetItemWindowHandle ( "NextAuctionDrawerWnd.NextAuctionItem" );
	txtItemDesc = GetTextBoxHandle( "NextAuctionDrawerWnd.scrollDesc.txtItemDesc" );
	txtItemName = GetTextBoxHandle( "NextAuctionDrawerWnd.txtItemName" );
	areaScroll = GetWindowHandle( "NextAuctionDrawerWnd.scrollDesc" );
	NextAuctionTime = GetTextBoxHandle( "NextAuctionDrawerWnd.NextAuctionTime" );
}

function OnEvent(int Event_ID, string param)
{
	switch(Event_ID)
	{
	case EV_ITEM_AUCTION_NEXT_INFO:
		HandleAuctionNextInfo(param);
		break;
	}
}

function HandleAuctionNextInfo(string param)
{
	local int startYear;
	local int startMon; 
	local int startDay; 
	local int startMin; 
	local int startHour;
	local int64 startPrice;
	local ItemInfo info;
	local ItemInfo curInfo;		//CT26P4

	//debug("HandleAuctionNextInfo param = " $ param);
	
	ParseInt(param, "startTimeYear", startYear);
	ParseInt(param, "startTimeMonth", startMon);
	ParseInt(param, "startTimeDay", startDay);
	ParseInt(param, "startTimeHour", startHour);
	ParseInt(param, "startTimeMinute", startMin);
	ParseInt64(param, "startPrice", startPrice);
	ParamToItemInfo( param, info );

	//CT26P4
	//일반 패킷에 Next 정보가 같이 와서 매초마다 이 함수가 불려서 여러 문제가 생김
	//아이템 정보가 바뀔 때만 아래 작업 수행하도록 변경 by enyheid
	AuctionItem.GetItem(0, curInfo);

	if (info.Id.ServerID != curInfo.Id.ServerID)
	{
		//debug("====== " $ info.Id.ServerID $ "," $ curInfo.Id.ServerID );
		AuctionItem.Clear();
		AuctionItem.AddItem( info );
		AuctionItem.SetTooltipType( "Inventory");
		
		txtItemName.SetText( info.Name );
		ComputeScrollHeight( info.Description );	//높이를 계산해준다. 
		txtItemDesc.SetText( info.Description );
	}

	if (startMin >= 10)
	{
		NextAuctionTime.SetText(startYear$"/"$startMon$"/"$startDay$"  "$startHour$":"$startMin);
	}
	
	else if ( startMin < 10 || startMin >= 0)
	{
		NextAuctionTime.SetText(startYear$"/"$startMon$"/"$startDay$"  "$startHour$":"$"0"$startMin);
	}
}

function ComputeScrollHeight(string tempStr)
{
	local int nHeight;
	local int nWidth;
	
	local Rect rectWnd;
	
	rectWnd = txtItemDesc.GetRect();
	
	GetTextSizeDefault(tempStr, nWidth, nHeight);		// 사이즈를 받아서
	
	areaScroll.SetScrollHeight((nHeight + 1) * (nWidth / rectWnd.nWidth + 1));
	areaScroll.SetScrollPosition( 0 );
}

function OnClickButton( string Name )
{
	switch(Name)
	{
	case "BtnClose":
		Me.HideWindow();
		BtnAuctionWndShowNext.EnableWindow();
		break;
	}
}
defaultproperties
{
}
