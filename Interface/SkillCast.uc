class SkillCast extends UICommonAPI;

var WindowHandle Me;
var WindowHandle MeTarget;
var ProgressCtrlHandle progressCast;
var TextBoxHandle skillName;
var TextureHandle skillTex;

var UserInfo TargetInfo;

var bool showSC;

var Color MsgColor;

function OnLoad ()
{
	local int temp;
	
	Me = GetWindowHandle("SkillCast");
	MeTarget = GetWindowHandle("TargetStatusWnd");
	progressCast = GetProgressCtrlHandle("SkillCast.progressCast");
	skillName = GetTextBoxHandle("SkillCast.skillName");
	skillTex = GetTextureHandle("SkillCast.skillTex");
	
	MeTarget.ShowWindow();
	Me.SetAnchor("TargetStatusWnd", "BottomLeft", "TopLeft", 0 , 0);
	Me.HideWindow();
	MeTarget.HideWindow();
	
	showSC = false;
	TargetInfo.nID = -1;
	MsgColor.R = 210;
	MsgColor.G = 190;
	MsgColor.B = 50;
	
	GetINIBool("WindowsCheks", "TargetCast", temp, "PatchSettings");
	if (temp == 1)
	{
		showSC = true;
	}
}


function OnRegisterEvent ()
{
	RegisterEvent(EV_ReceiveMagicSkillUse);
	RegisterEvent(EV_TargetUpdate);
}


function OnEvent (int a_EventID, string a_Param)
{	
	local int TargetID;	
	
	switch (a_EventID)
	{
		case EV_TargetUpdate:
			TargetID = -1;
			TargetID = class'UIDATA_TARGET'.static.GetTargetID();
			if (TargetInfo.nID != TargetID)
			{
				GetTargetInfo(TargetInfo);
				Me.HideWindow();
			}
			else
			{
				return;
			}		
			break;			
			
		case EV_ReceiveMagicSkillUse:
			if (showSC)
			{
				if (TargetInfo.bNpc && !TargetInfo.bPet && !TargetInfo.bCanBeAttacked) 
					return;
				else
					if (TargetInfo.nID > 0) HandleReceiveMagicSkillUse(a_Param);						
			}
			break;	
	}
}


function OnEnterState( name a_PreStateName )
{
	TargetInfo.nID = -1;
	Me.HideWindow();
}


function OnTimer (int TimerID)
{
	if ( TimerID == 15512 )
	{
		Me.KillTimer(15512);
		Me.HideWindow();
	}
}

function HandleReceiveMagicSkillUse (string a_Param)
{
	local int AttackerID;
	local int SkillID;
	local int SkillLevel;
	local float SkillHitTime;
	local int SkillHitTime_ms;
	local SkillInfo UsedSkillInfo;
	
	ParseInt(a_Param,"AttackerID",AttackerID);
	if (TargetInfo.nID != AttackerID) return;

	ParseInt(a_Param,"SkillID",SkillID);
	if (IsNotDisplaySkill(SkillID)) return;
  
	if (MeTarget.IsShowWindow())
	{
		ParseInt(a_Param,"SkillID",SkillID);
		ParseInt(a_Param,"SkillLevel",SkillLevel);
		ParseFloat(a_Param,"SkillHitTime",SkillHitTime);
		if (SkillHitTime > 0)
		{
			SkillHitTime_ms = int(SkillHitTime * 1000) + 300;
		}
		else
		{
			SkillHitTime_ms = 100;
		}
		GetSkillInfo(SkillID,SkillLevel,UsedSkillInfo);

		Me.HideWindow();
		SetCastInfo(UsedSkillInfo,SkillHitTime_ms);	
	}
}

function SetCastInfo (SkillInfo CastInfo, int SkillHitTime_ms)
{
	local int nWndWidth, nWndHeight;
	
	
  	MeTarget.GetWindowSize(nWndWidth , nWndHeight);
	
	if (nWndHeight == 55)
		Me.SetWindowSize(nWndWidth - 27 , 42);
	else
		Me.SetWindowSize(nWndWidth - 45 , 42);
	
	Me.KillTimer(15512);
	

	skillTex.SetTexture(CastInfo.TexName); // i.e. icon.skill1337
	if (CastInfo.EnchantName == "none") 
		skillName.SetText(CastInfo.SkillName);
	else 
		skillName.SetText(CastInfo.SkillName $" " $ "("$ CastInfo.EnchantName $")" );
  
	//SetProgressTexture(rand(12)); 
	progressCast.SetProgressTime(SkillHitTime_ms);
	progressCast.SetPos(SkillHitTime_ms);
	progressCast.Reset();
	Me.ShowWindow();
	progressCast.Start();
	
}


function SetProgressTexture (int idx)
{
	local string textPath;
	textPath = "MonIcaTex.ProgressBar.progress_";	
	switch (idx)
	{
		case 0:
		case 1:
		case 2:
			progressCast.SetBackTex(textPath $ "White_mid_bg",textPath $ "White_mid_bg",textPath $ "White_mid_bg");
			progressCast.SetBarTex(textPath $ "White_mid",textPath $ "White_mid",textPath $ "White_mid");
		break;
		case 3:
		case 5:
			progressCast.SetBackTex(textPath $ "Red_mid_bg",textPath $ "Red_mid_bg",textPath $ "Red_mid_bg");
			progressCast.SetBarTex(textPath $ "Red_mid",textPath $ "Red_mid",textPath $ "Red_mid");

		break;
		case 4:
			progressCast.SetBackTex(textPath $ "Pink_mid_bg",textPath $ "Pink_mid" $ "_bg",textPath $ "Pink_mid_bg");
			progressCast.SetBarTex(textPath $ "Pink_mid",textPath $ "Pink_mid",textPath $ "Pink_mid");
		break;
		case 6:
			progressCast.SetBackTex(textPath $ "Orange_mid_bg",textPath $ "Orange_mid_bg",textPath $ "Orange_mid_bg");
			progressCast.SetBarTex(textPath $ "Orange_mid",textPath $ "Orange_mid",textPath $ "Orange_mid");
		break;
		case 7:
			progressCast.SetBackTex(textPath $ "Yellow_mid_bg",textPath $ "Yellow_mid" $ "_bg",textPath $ "Yellow_mid_bg");
			progressCast.SetBarTex(textPath $ "Yellow_mid",textPath $ "Yellow_mid",textPath $ "Yellow_mid");
		break;
		case 8:
			progressCast.SetBackTex(textPath $ "Blue_mid_bg",textPath $ "Blue_mid" $ "_bg",textPath $ "Blue_mid_bg");
			progressCast.SetBarTex(textPath $ "Blue_mid",textPath $ "Blue_mid",textPath $ "Blue_mid");
		break;
		case 9:
			progressCast.SetBackTex(textPath $ "Cyan_mid_bg",textPath $ "Cyan_mid" $ "_bg",textPath $ "Cyan_mid_bg");
			progressCast.SetBarTex(textPath $ "Cyan_mid",textPath $ "Cyan_mid",textPath $ "Cyan_mid");
		break;
		case 10:
			progressCast.SetBackTex(textPath $ "Green_mid_bg",textPath $ "Green_mid" $ "_bg",textPath $ "Green_mid_bg");
			progressCast.SetBarTex(textPath $ "Green_mid",textPath $ "Green_mid",textPath $ "Green_mid");
		break;
		case 11:
			progressCast.SetBackTex(textPath $ "Purple_mid_bg",textPath $ "Purple_mid" $ "_bg",textPath $ "Purple_mid_bg");
			progressCast.SetBarTex(textPath $ "Purple_mid",textPath $ "Purple_mid",textPath $ "Purple_mid");
		break;
	}
}


function OnProgressTimeUp (string strID)
{  
  switch (strID)
  {
    case "progressCast":
		Me.KillTimer(15512);
		Me.SetTimer(15512,500);
		break;
  }
}


defaultproperties
{
}