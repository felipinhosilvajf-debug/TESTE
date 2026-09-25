unit UnitMain;

interface

uses
  Windows, Messages, SysUtils, Variants, Classes, Graphics, Controls, Forms,
  Dialogs, Gauges, Buttons, IniFiles, StdCtrls, OleCtrls, SHDocVw, ExtCtrls,
  Wininet, ImgBtn, ComCtrls, ShlObj, ComObj, ActiveX, jpeg, Registry;

function ReadLauncherInteger(const Name: string; DefaultValue: Integer): Integer;
procedure WriteLauncherInteger(const Name: string; Value: Integer);

type
  TFMain = class(TForm)
    Gauge1: TGauge;
    Gauge2: TGauge;
    Image1: TImage;
    Label1: TLabel;
    Label2: TLabel;
    Label3: TLabel;
    ImgBtn1: TImgBtn;
    ImgBtn2: TImgBtn;
    ImgBtn3: TImgBtn;
    ImgBtn4: TImgBtn;
    Panel1: TPanel;
    WebBrowser1: TWebBrowser;
    Image2: TImage;
    Image3: TImage;
    Image4: TImage;
    Label4: TLabel;
    ImgBtn5: TImgBtn;
    Timer1: TTimer;
    procedure FormCreate(Sender: TObject);
    procedure FormMouseDown(Sender: TObject; Button: TMouseButton;
      Shift: TShiftState; X, Y: Integer);
    procedure FormMouseMove(Sender: TObject; Shift: TShiftState; X,
      Y: Integer);
    procedure FormMouseUp(Sender: TObject; Button: TMouseButton;
      Shift: TShiftState; X, Y: Integer);
    procedure ImgBtn4Click(Sender: TObject);
    procedure ImgBtn3Click(Sender: TObject);
    procedure ImgBtn2Click(Sender: TObject);
    procedure ImgBtn1Click(Sender: TObject);
    procedure FormClose(Sender: TObject; var Action: TCloseAction);
    procedure ImgBtn5Click(Sender: TObject);
    procedure WebBrowser1NavigateComplete2(Sender: TObject;
      const pDisp: IDispatch; var URL: OleVariant);
    procedure Timer1Timer(Sender: TObject);
    procedure UpdateRevision(Rev: string);
  private
    { Private declarations }
  public
    Draging: Boolean;
    X0, Y0: Integer;
  end;

var
  FMain: TFMain;
  USettings: TStrings;

const
  REG_PATH = 'Software\VirtualBoost\L2Launcher';

  DEFAULT_NEWS_URL =
    'https://virtualboost.online/update/Web/index1.html';

  DEFAULT_UPDATES_URL =
    'https://virtualboost.online/update';

  DEFAULT_LINK_NAME =
    'L2 Virtual Boost';

  DEFAULT_RUN_CUSTOM =
    'system\l2.exe';

implementation

uses Frm2, GetFilesThr, Misc;

{$R *.dfm}

function ReadLauncherInteger(const Name: string; DefaultValue: Integer): Integer;
var
  Reg: TRegistry;
begin
  Result := DefaultValue;

  Reg := TRegistry.Create;
  try
    Reg.RootKey := HKEY_CURRENT_USER;

    if Reg.OpenKey(REG_PATH, False) then
    begin
      if Reg.ValueExists(Name) then
        Result := Reg.ReadInteger(Name);

      Reg.CloseKey;
    end;
  finally
    Reg.Free;
  end;
end;

procedure WriteLauncherInteger(const Name: string; Value: Integer);
var
  Reg: TRegistry;
begin
  Reg := TRegistry.Create;
  try
    Reg.RootKey := HKEY_CURRENT_USER;

    if Reg.OpenKey(REG_PATH, True) then
    begin
      Reg.WriteInteger(Name, Value);
      Reg.CloseKey;
    end;
  finally
    Reg.Free;
  end;
end;

procedure TFMain.UpdateRevision(Rev: string);
begin
  WriteLauncherInteger('AtRevision', StrToIntDef(Rev, 0));

  if Assigned(USettings) and (USettings.Count > 6) then
    USettings[6] := Rev;
end;

function LoadSettings(): Boolean;
begin
  Result := True;

  USettings := TStringList.Create;

  { [0] Diretório do launcher }
  USettings.Add(IncludeTrailingPathDelimiter(GetCurrentDir));

  { [1] NewsUrl }
  USettings.Add(DEFAULT_NEWS_URL);

  { [2] UpdatesUrl }
  USettings.Add(DEFAULT_UPDATES_URL);

  { [3] LinkName }
  USettings.Add(DEFAULT_LINK_NAME);

  { [4] Installed }
  USettings.Add('1');

  { [5] CreateBackup }
  USettings.Add(
    IntToStr(ReadLauncherInteger('CreateBackup', 0))
  );

  { [6] AtRevision }
  USettings.Add(
    IntToStr(ReadLauncherInteger('AtRevision', 0))
  );

  { [7] Executável do jogo }
  USettings.Add(DEFAULT_RUN_CUSTOM);
end;

// cria atalho na area de trabalho
procedure CreateDesktopIcon(ilname, WorkDir, desc: string);
var
  IObj: IUnknown;
  SLink: IShellLink;
  PFile: IPersistFile;
  desk: string;
  lnkpath: WideString;
begin
  if (ilname <> '') then
  begin
    SetLength(desk, MAX_PATH + 1);
    SHGetSpecialFolderPath(0, PAnsiChar(desk),
      CSIDL_DESKTOPDIRECTORY, False);

    lnkpath := PChar(desk) + '\' + ilname + '.lnk';

    IObj := CreateComObject(CLSID_ShellLink);
    SLink := IObj as IShellLink;
    PFile := IObj as IPersistFile;

    with SLink do
    begin
      SetDescription(PChar(desc));
      SetPath(PChar(Application.ExeName));
      SetWorkingDirectory(PAnsiChar(WorkDir));
    end;

    PFile.Save(PWChar(WideString(lnkpath)), FALSE);
  end;
end;

procedure TFMain.FormCreate(Sender: TObject);
var
  regn, tmpRegn, x, y: Integer;
  nullClr: TColor;
  s_load: Boolean;
  Settings: TIniFile;
begin
  s_load := LoadSettings();

  if (not s_load) then
  begin
    FMain.Timer1.Enabled := False;
    ShowMessage('ERROR: Launcher settings could not be loaded.');
    Application.Terminate;
  end;

  // Navegacao visual da forma
  FMain.Brush.Bitmap := Image1.Picture.Bitmap;

  nullClr := Image1.Picture.Bitmap.Canvas.Pixels[0, 0];

  regn := CreateRectRgn(
    0,
    0,
    Image1.Picture.Graphic.Width,
    Image1.Picture.Graphic.Height
  );

  for x := 1 to Image1.Picture.Graphic.Width do
    for y := 1 to Image1.Picture.Graphic.Height do
      if Image1.Picture.Bitmap.Canvas.Pixels[x - 1, y - 1] = nullClr then
      begin
        tmpRegn := CreateRectRgn(x - 1, y - 1, x, y);
        CombineRgn(regn, regn, tmpRegn, RGN_DIFF);
        DeleteObject(tmpRegn);
      end;

  SetWindowRgn(FMain.Handle, regn, True);
end;

procedure TFMain.FormMouseDown(Sender: TObject; Button: TMouseButton;
  Shift: TShiftState; X, Y: Integer);
begin
  Draging := True;
  X0 := X;
  Y0 := Y;
end;

procedure TFMain.FormMouseUp(Sender: TObject; Button: TMouseButton;
  Shift: TShiftState; X, Y: Integer);
begin
  Draging := False;
end;

procedure TFMain.FormMouseMove(Sender: TObject; Shift: TShiftState;
  X, Y: Integer);
begin
  if Draging = True then
  begin
    FMain.Left := FMain.Left + X - X0;
    FMain.Top := FMain.Top + Y - Y0;
  end;
end;

procedure TFMain.ImgBtn4Click(Sender: TObject);
begin
  FMain.Close;
end;

procedure TFMain.ImgBtn3Click(Sender: TObject);
begin
  FMain.Close;
end;

procedure TFMain.ImgBtn2Click(Sender: TObject);
var
  WThread: GFilesThread;
begin
  Label3.Caption := '';

  WThread := GFilesThread.Create(True);
  WThread.FreeOnTerminate := True;

  WThread.UpdatesUrl := USettings[2];
  WThread.ForceCheck := True;
  WThread.CreateBackup := StrToInt(USettings[5]);
  WThread.LocalRevision := StrToInt(USettings[6]);

  WThread.Resume;
end;

procedure TFMain.ImgBtn1Click(Sender: TObject);
begin
  RunApp(USettings[0] + USettings[7]);
  FMain.Close;
end;

procedure TFMain.FormClose(Sender: TObject; var Action: TCloseAction);
begin
  USettings.Free;
end;

procedure TFMain.ImgBtn5Click(Sender: TObject);
begin
  FMain.Enabled := False;
  Form1.Show;
end;

procedure TFMain.WebBrowser1NavigateComplete2(Sender: TObject;
  const pDisp: IDispatch; var URL: OleVariant);
begin
  FMain.Panel1.Visible := True;
  FMain.Image2.Visible := True;
  FMain.Image3.Visible := True;
  FMain.Image4.Visible := True;
end;

procedure TFMain.Timer1Timer(Sender: TObject);
var
  WThread: GFilesThread;
begin
  FMain.Timer1.Enabled := False;

  WebBrowser1.Navigate(USettings[1]);
  Label3.Caption := '';

  WThread := GFilesThread.Create(True);
  WThread.FreeOnTerminate := True;

  WThread.UpdatesUrl := USettings[2];
  WThread.ForceCheck := False;
  WThread.CreateBackup := StrToInt(USettings[5]);
  WThread.LocalRevision := StrToInt(USettings[6]);

  WThread.Resume;
end;

end.
