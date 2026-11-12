using System.Diagnostics;
using System.Drawing.Drawing2D;

namespace VirtualBoostLauncher;

public sealed class MainForm : Form
{
    private readonly LauncherService _launcher = new();
    private readonly TextBox _nickname = new();
    private readonly Label _clientStatus = new();
    private readonly Label _gameStatus = new();
    private readonly Label _message = new();
    private readonly TextBox _gamePath = new();
    private readonly Button _installClientButton = new();
    private ClientInstallation? _installation;

    private static readonly Color BackgroundColor = Color.FromArgb(9, 13, 24);
    private static readonly Color PanelColor = Color.FromArgb(17, 24, 39);
    private static readonly Color AccentColor = Color.FromArgb(184, 255, 59);
    private static readonly Color MutedColor = Color.FromArgb(164, 176, 196);

    public MainForm()
    {
        Text = "Virtual Boost Launcher";
        StartPosition = FormStartPosition.CenterScreen;
        MinimumSize = new Size(1040, 760);
        Size = new Size(900, 700);
        BackColor = BackgroundColor;
        ForeColor = Color.White;
        Font = new Font("Segoe UI", 10F);
        FormBorderStyle = FormBorderStyle.Sizable;
        MaximizeBox = true;
        AutoScaleMode = AutoScaleMode.Dpi;

        BuildInterface();
        Shown += (_, _) => DetectInstallations();
    }

    private void BuildInterface()
    {
        var root = new TableLayoutPanel
        {
            Dock = DockStyle.Fill,
            ColumnCount = 1,
            RowCount = 5,
            Padding = new Padding(30, 22, 30, 22),
            BackColor = BackgroundColor
        };
        root.RowStyles.Add(new RowStyle(SizeType.Absolute, 74));
        root.RowStyles.Add(new RowStyle(SizeType.Absolute, 250));
        root.RowStyles.Add(new RowStyle(SizeType.Absolute, 112));
        root.RowStyles.Add(new RowStyle(SizeType.Absolute, 110));
        root.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        Controls.Add(root);

        var header = new Panel { Dock = DockStyle.Fill, BackColor = BackgroundColor };
        var brand = new Label
        {
            Text = "VIRTUAL BOOST",
            Font = new Font("Segoe UI", 24F, FontStyle.Bold),
            ForeColor = AccentColor,
            AutoSize = true,
            Location = new Point(0, 8)
        };
        var subtitle = new Label
        {
            Text = "SAMP RPG  •  ANDROID & PC",
            Font = new Font("Segoe UI", 9F, FontStyle.Bold),
            ForeColor = MutedColor,
            AutoSize = true,
            Location = new Point(3, 47)
        };
        header.Controls.Add(brand);
        header.Controls.Add(subtitle);
        root.Controls.Add(header, 0, 0);

        var hero = new Panel
        {
            Dock = DockStyle.Fill,
            BackColor = PanelColor,
            Padding = new Padding(24)
        };
        var backgroundPath = Path.Combine(AppContext.BaseDirectory, "roleplay-bg.jpg");
        if (File.Exists(backgroundPath))
        {
            try
            {
                hero.BackgroundImage = Image.FromFile(backgroundPath);
                hero.BackgroundImageLayout = ImageLayout.Stretch;
            }
            catch { DrawRolePlayBackdrop(hero); }
        }
        else
        {
            DrawRolePlayBackdrop(hero);
        }
        hero.Paint += (_, e) =>
        {
            using var overlay = new SolidBrush(Color.FromArgb(125, 4, 8, 18));
            e.Graphics.FillRectangle(overlay, hero.ClientRectangle);
            using var pen = new Pen(Color.FromArgb(100, AccentColor), 1);
            e.Graphics.DrawRectangle(pen, 0, 0, hero.Width - 1, hero.Height - 1);
        };
        root.Controls.Add(hero, 0, 1);

        var welcome = new Label
        {
            Text = "SUA CIDADE. SUA HISTÓRIA.",
            Font = new Font("Segoe UI", 21F, FontStyle.Bold),
            ForeColor = Color.White,
            AutoSize = true,
            Location = new Point(28, 26)
        };
        var serverLabel = new Label
        {
            Text = "IP OFICIAL DO SERVIDOR",
            Font = new Font("Segoe UI", 8.5F, FontStyle.Bold),
            ForeColor = MutedColor,
            AutoSize = true,
            Location = new Point(31, 83)
        };
        var serverAddress = new Label
        {
            Text = $"{LauncherService.ServerHost}:{LauncherService.ServerPort}",
            Font = new Font("Segoe UI", 22F, FontStyle.Bold),
            ForeColor = AccentColor,
            AutoSize = true,
            Location = new Point(28, 104)
        };
        var play = new Button
        {
            Text = "JOGAR AGORA",
            Font = new Font("Segoe UI", 13F, FontStyle.Bold),
            BackColor = AccentColor,
            ForeColor = Color.FromArgb(12, 18, 8),
            FlatStyle = FlatStyle.Flat,
            Size = new Size(250, 54),
            Location = new Point(31, 166),
            Cursor = Cursors.Hand
        };
        play.FlatAppearance.BorderSize = 0;
        play.Click += (_, _) => Launch();
        hero.Controls.AddRange([welcome, serverLabel, serverAddress, play]);

        var nickPanel = CreatePanel();
        root.Controls.Add(nickPanel, 0, 2);
        var nickLabel = CreateLabel("SEU NICK", 9F, MutedColor, true);
        nickLabel.Location = new Point(18, 13);
        _nickname.Text = "";
        _nickname.PlaceholderText = "Digite seu nick";
        _nickname.MaxLength = 20;
        _nickname.Location = new Point(18, 39);
        _nickname.Size = new Size(300, 34);
        StyleTextBox(_nickname);
        nickPanel.Controls.Add(nickLabel);
        nickPanel.Controls.Add(_nickname);

        var statusPanel = CreatePanel();
        root.Controls.Add(statusPanel, 0, 3);
        _clientStatus.Text = "CLIENTE: procurando SA-MP / open.mp...";
        _clientStatus.Location = new Point(18, 13);
        _clientStatus.AutoSize = true;
        _clientStatus.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
        _clientStatus.ForeColor = Color.White;
        _gameStatus.Text = "GTA SAN ANDREAS: procurando instalação...";
        _gameStatus.Location = new Point(18, 43);
        _gameStatus.AutoSize = true;
        _gameStatus.Font = new Font("Segoe UI", 9F);
        _gameStatus.ForeColor = MutedColor;
        statusPanel.Controls.Add(_clientStatus);
        statusPanel.Controls.Add(_gameStatus);

        var bottom = new Panel { Dock = DockStyle.Fill, BackColor = BackgroundColor };
        root.Controls.Add(bottom, 0, 4);
        var folderLabel = CreateLabel("PASTA DO GTA SAN ANDREAS (OPCIONAL)", 8F, MutedColor, true);
        folderLabel.Location = new Point(0, 5);
        folderLabel.AutoSize = true;
        _gamePath.Location = new Point(0, 29);
        _gamePath.Size = new Size(600, 30);
        StyleTextBox(_gamePath);
        var browse = new Button
        {
            Text = "LOCALIZAR",
            Location = new Point(610, 28),
            Size = new Size(120, 32),
            FlatStyle = FlatStyle.Flat,
            BackColor = Color.FromArgb(31, 43, 61),
            ForeColor = Color.White
        };
        browse.FlatAppearance.BorderColor = Color.FromArgb(65, 80, 101);
        browse.Click += (_, _) => BrowseGameFolder();
        var refresh = new Button
        {
            Text = "DETECTAR NOVAMENTE",
            Location = new Point(0, 72),
            Size = new Size(190, 31),
            FlatStyle = FlatStyle.Flat,
            BackColor = Color.FromArgb(31, 43, 61),
            ForeColor = Color.White
        };
        refresh.FlatAppearance.BorderColor = Color.FromArgb(65, 80, 101);
        refresh.Click += (_, _) => DetectInstallations();

        _installClientButton.Text = "INSTALAR CLIENTE";
        _installClientButton.Location = new Point(205, 72);
        _installClientButton.Size = new Size(170, 31);
        _installClientButton.FlatStyle = FlatStyle.Flat;
        _installClientButton.BackColor = Color.FromArgb(31, 43, 61);
        _installClientButton.ForeColor = AccentColor;
        _installClientButton.Cursor = Cursors.Hand;
        _installClientButton.FlatAppearance.BorderColor = Color.FromArgb(65, 80, 101);
        _installClientButton.Click += async (_, _) => await InstallClientAsync();

        _message.Text = "O GTA precisa estar instalado no computador.";
        _message.Location = new Point(390, 78);
        _message.AutoSize = true;
        _message.ForeColor = MutedColor;
        _message.Font = new Font("Segoe UI", 8F);
        bottom.Controls.AddRange([folderLabel, _gamePath, browse, refresh, _installClientButton, _message]);
    }

    private static void DrawRolePlayBackdrop(Panel panel)
    {
        panel.Paint += (_, e) =>
        {
            var g = e.Graphics;
            var r = panel.ClientRectangle;
            using var sky = new LinearGradientBrush(r, Color.FromArgb(30, 34, 78), Color.FromArgb(5, 10, 22), 90F);
            g.FillRectangle(sky, r);
            var random = new Random(17);
            for (var i = 0; i < 34; i++)
            {
                int w = random.Next(18, 54);
                int h = random.Next(45, 145);
                int x = i * Math.Max(1, r.Width / 34);
                int y = r.Height - h - 18;
                using var building = new SolidBrush(Color.FromArgb(180, 7, 13, 27));
                g.FillRectangle(building, x, y, w, h);
                using var lights = new SolidBrush(Color.FromArgb(150, 184, 255, 59));
                for (int wy = y + 9; wy < r.Height - 24; wy += 17)
                    for (int wx = x + 6; wx < x + w - 4; wx += 13)
                        if (random.Next(3) == 0) g.FillRectangle(lights, wx, wy, 3, 5);
            }
            using var road = new LinearGradientBrush(new Rectangle(0, r.Height - 68, r.Width, 68),
                Color.FromArgb(15, 20, 30), Color.FromArgb(2, 4, 10), 90F);
            g.FillRectangle(road, 0, r.Height - 68, r.Width, 68);
            using var roadPen = new Pen(Color.FromArgb(170, 184, 255, 59), 2);
            g.DrawLine(roadPen, 0, r.Height - 20, r.Width, r.Height - 20);
        };
    }

    private Panel CreatePanel() => new()
    {
        Dock = DockStyle.Fill,
        BackColor = PanelColor,
        Padding = new Padding(16)
    };

    private static Label CreateLabel(string text, float size, Color color, bool bold) => new()
    {
        Text = text,
        Font = new Font("Segoe UI", size, bold ? FontStyle.Bold : FontStyle.Regular),
        ForeColor = color,
        AutoSize = true
    };

    private static void StyleTextBox(TextBox textBox)
    {
        textBox.BackColor = Color.FromArgb(8, 13, 22);
        textBox.ForeColor = Color.White;
        textBox.BorderStyle = BorderStyle.FixedSingle;
        textBox.Font = new Font("Segoe UI", 10F);
    }

    private void DetectInstallations()
    {
        _installation = _launcher.DetectClient(_gamePath.Text);
        var gameDirectory = _launcher.FindGameDirectory(_gamePath.Text);

        if (gameDirectory is not null)
        {
            _gamePath.Text = gameDirectory;
            _gameStatus.Text = $"GTA SAN ANDREAS: encontrado em {gameDirectory}";
            _gameStatus.ForeColor = AccentColor;
        }
        else
        {
            _gameStatus.Text = "GTA SAN ANDREAS: não encontrado. Use LOCALIZAR.";
            _gameStatus.ForeColor = Color.FromArgb(255, 190, 90);
        }

        if (_installation is not null)
        {
            _clientStatus.Text = $"CLIENTE: {_installation.DisplayName} detectado automaticamente";
            _clientStatus.ForeColor = AccentColor;
            _message.Text = "Pronto para iniciar a conexão com o Virtual Boost.";
            _installClientButton.Text = "CLIENTE INSTALADO";
            _installClientButton.Enabled = false;
        }
        else
        {
            _clientStatus.Text = "CLIENTE: SA-MP / open.mp não encontrado";
            _clientStatus.ForeColor = Color.FromArgb(255, 190, 90);
            _message.Text = "Clique em INSTALAR CLIENTE para baixar o open.mp oficial.";
            _installClientButton.Text = "INSTALAR CLIENTE";
            _installClientButton.Enabled = true;
        }
    }

    private async Task InstallClientAsync()
    {
        try
        {
            _installClientButton.Enabled = false;
            _installClientButton.Text = "BAIXANDO...";
            _message.Text = "Baixando instalador oficial do open.mp...";
            var progress = new Progress<int>(value =>
            {
                _installClientButton.Text = $"BAIXANDO {value}%";
                _message.Text = $"Download do cliente open.mp: {value}%";
            });

            var installerPath = await ClientInstaller.DownloadOpenMpInstallerAsync(progress);
            _message.Text = "Download verificado. Abrindo instalação do open.mp...";

            using var installer = Process.Start(new ProcessStartInfo
            {
                FileName = installerPath,
                UseShellExecute = true
            });

            if (installer is null)
                throw new InvalidOperationException("O Windows não conseguiu abrir o instalador do open.mp.");

            await installer.WaitForExitAsync();
            DetectInstallations();

            if (_installation is null)
                _message.Text = "Instalação encerrada. Se cancelou o assistente, clique em INSTALAR CLIENTE novamente.";
            else
                _message.Text = "Cliente detectado! Agora você já pode jogar no Virtual Boost.";
        }
        catch (Exception ex)
        {
            _message.Text = "Não foi possível instalar o cliente.";
            MessageBox.Show(this, ex.Message, "Instalação do cliente", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
        finally
        {
            if (!IsDisposed && _installation is null)
            {
                _installClientButton.Enabled = true;
                _installClientButton.Text = "INSTALAR CLIENTE";
            }
        }
    }

    private void BrowseGameFolder()
    {
        using var dialog = new FolderBrowserDialog
        {
            Description = "Selecione a pasta onde está o arquivo gta_sa.exe",
            UseDescriptionForTitle = true
        };

        if (dialog.ShowDialog(this) != DialogResult.OK)
            return;

        if (!File.Exists(Path.Combine(dialog.SelectedPath, "gta_sa.exe")))
        {
            MessageBox.Show(this, "Não encontrei gta_sa.exe nessa pasta. Selecione a pasta correta do GTA San Andreas.",
                "Pasta inválida", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            return;
        }

        _gamePath.Text = dialog.SelectedPath;
        DetectInstallations();
    }

    private void Launch()
    {
        try
        {
            LauncherService.ValidateNickname(_nickname.Text.Trim());
            DetectInstallations();

            if (_installation is null)
            {
                MessageBox.Show(this,
                    "Não encontrei um cliente SA-MP ou open.mp compatível. Instale um deles e clique em DETECTAR NOVAMENTE.",
                    "Cliente não encontrado", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            _message.Text = $"Abrindo {_installation.DisplayName}...";
            var process = _launcher.StartGame(_installation, _nickname.Text.Trim());
            _message.Text = $"Cliente iniciado (PID {process.Id}). Se o jogo não conectar, confira a versão do cliente.";
        }
        catch (Exception ex)
        {
            _message.Text = "Não foi possível iniciar o jogo.";
            MessageBox.Show(this, ex.Message, "Virtual Boost Launcher", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}