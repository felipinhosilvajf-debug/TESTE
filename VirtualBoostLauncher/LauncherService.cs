using Microsoft.Win32;
using System.Diagnostics;

namespace VirtualBoostLauncher;

public enum MultiplayerClient
{
    OpenMp,
    Samp
}

public sealed record ClientInstallation(MultiplayerClient Type, string ExecutablePath, string GameDirectory)
{
    public string DisplayName => Type == MultiplayerClient.OpenMp ? "open.mp" : "SA-MP";
}

public sealed class LauncherService
{
    public const string ServerHost = "163.245.202.163";
    public const int ServerPort = 7777;

    private static readonly string[] GameDirectoryCandidates =
    [
        @"C:\Program Files (x86)\Rockstar Games\GTA San Andreas",
        @"C:\Program Files\Rockstar Games\GTA San Andreas",
        @"C:\Program Files (x86)\Rockstar Games\Grand Theft Auto San Andreas",
        @"C:\Games\GTA San Andreas",
        @"C:\Games\GTA San Andreas 1.0",
        @"C:\GTA San Andreas"
    ];

    public ClientInstallation? DetectClient(string? preferredGameDirectory = null)
    {
        var gameDirectory = FindGameDirectory(preferredGameDirectory);
        if (gameDirectory is null)
            return null;

        // Se ambos estiverem instalados, prioriza open.mp; SA-MP fica como alternativa automática.
        var openMp = FindOpenMpLauncher(gameDirectory);
        if (openMp is not null)
            return new ClientInstallation(MultiplayerClient.OpenMp, openMp, gameDirectory);

        var samp = Path.Combine(gameDirectory, "samp.exe");
        if (File.Exists(samp))
            return new ClientInstallation(MultiplayerClient.Samp, samp, gameDirectory);

        return null;
    }

    public string? FindGameDirectory(string? preferredDirectory = null)
    {
        var candidates = new List<string>();
        if (!string.IsNullOrWhiteSpace(preferredDirectory))
            candidates.Add(preferredDirectory);

        candidates.AddRange(ReadGameDirectoriesFromRegistry());
        candidates.AddRange(GameDirectoryCandidates);

        foreach (var directory in candidates.Distinct(StringComparer.OrdinalIgnoreCase))
        {
            if (Directory.Exists(directory) && File.Exists(Path.Combine(directory, "gta_sa.exe")))
                return Path.GetFullPath(directory);
        }

        return null;
    }

    public string? FindOpenMpLauncher(string gameDirectory)
    {
        var candidates = new List<string>
        {
            Path.Combine(gameDirectory, "omp-launcher.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "Programs", "open.mp Launcher", "omp-launcher.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "Programs", "omp-launcher", "omp-launcher.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "mp.open.launcher", "omp-launcher.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "open.mp Launcher", "omp-launcher.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "open.mp", "omp-launcher.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), "open.mp Launcher", "omp-launcher.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), "open.mp", "omp-launcher.exe")
        };

        foreach (var path in candidates.Distinct(StringComparer.OrdinalIgnoreCase))
        {
            if (File.Exists(path))
                return Path.GetFullPath(path);
        }

        // Também consulta o registro do Windows, onde instaladores podem guardar a pasta.
        foreach (var path in ReadOpenMpLauncherFromRegistry())
        {
            if (File.Exists(path))
                return Path.GetFullPath(path);
        }

        return null;
    }

    public Process StartGame(ClientInstallation installation, string nickname)
    {
        ValidateNickname(nickname);

        var startInfo = new ProcessStartInfo
        {
            FileName = installation.ExecutablePath,
            WorkingDirectory = installation.Type == MultiplayerClient.Samp
                ? installation.GameDirectory
                : Path.GetDirectoryName(installation.ExecutablePath) ?? installation.GameDirectory,
            UseShellExecute = false
        };

        if (installation.Type == MultiplayerClient.OpenMp)
        {
            // Parâmetros documentados do omp-launcher.exe.
            startInfo.ArgumentList.Add("-h");
            startInfo.ArgumentList.Add(ServerHost);
            startInfo.ArgumentList.Add("-p");
            startInfo.ArgumentList.Add(ServerPort.ToString());
            startInfo.ArgumentList.Add("-n");
            startInfo.ArgumentList.Add(nickname);
            startInfo.ArgumentList.Add("-g");
            startInfo.ArgumentList.Add(installation.GameDirectory);
        }
        else
        {
            // O cliente SA-MP recebe o endereço como primeiro argumento.
            startInfo.ArgumentList.Add($"{ServerHost}:{ServerPort}");
            startInfo.ArgumentList.Add("-n");
            startInfo.ArgumentList.Add(nickname);
            startInfo.ArgumentList.Add("-c");
        }

        return Process.Start(startInfo)
            ?? throw new InvalidOperationException("O Windows não conseguiu iniciar o cliente multiplayer.");
    }

    public static void ValidateNickname(string nickname)
    {
        if (string.IsNullOrWhiteSpace(nickname))
            throw new ArgumentException("Digite seu nick antes de jogar.");

        if (nickname.Length < 3 || nickname.Length > 24)
            throw new ArgumentException("O nick precisa ter entre 3 e 24 caracteres.");

        if (nickname.Any(ch => !(char.IsLetterOrDigit(ch) || ch == '_' || ch == '.')))
            throw new ArgumentException("Use somente letras, números, ponto ou underline no nick.");
    }

    private static IEnumerable<string> ReadOpenMpLauncherFromRegistry()
    {
        var uninstallPath = @"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall";
        foreach (var view in new[] { RegistryView.Registry64, RegistryView.Registry32 })
        {
            using var baseKey = RegistryKey.OpenBaseKey(RegistryHive.LocalMachine, view);
            using var uninstall = baseKey.OpenSubKey(uninstallPath);
            if (uninstall is null)
                continue;

            foreach (var subName in uninstall.GetSubKeyNames())
            {
                using var app = uninstall.OpenSubKey(subName);
                var displayName = app?.GetValue("DisplayName")?.ToString() ?? "";
                if (!displayName.Contains("open.mp", StringComparison.OrdinalIgnoreCase))
                    continue;

                var installLocation = app?.GetValue("InstallLocation")?.ToString();
                if (!string.IsNullOrWhiteSpace(installLocation))
                    yield return Path.Combine(installLocation, "omp-launcher.exe");

                var displayIcon = app?.GetValue("DisplayIcon")?.ToString();
                if (!string.IsNullOrWhiteSpace(displayIcon))
                {
                    var iconPath = displayIcon.Trim().Trim('"');
                    var comma = iconPath.IndexOf(',');
                    if (comma >= 0)
                        iconPath = iconPath[..comma].Trim().Trim('"');
                    if (Path.GetFileName(iconPath).Equals("omp-launcher.exe", StringComparison.OrdinalIgnoreCase))
                        yield return iconPath;
                }
            }
        }
    }

    private static IEnumerable<string> ReadGameDirectoriesFromRegistry()
    {
        const string subKey = @"SOFTWARE\Rockstar Games\GTA San Andreas";
        foreach (var view in new[] { RegistryView.Registry64, RegistryView.Registry32 })
        {
            using var baseKey = RegistryKey.OpenBaseKey(RegistryHive.LocalMachine, view);
            using var key = baseKey.OpenSubKey(subKey);
            var install = key?.GetValue("InstallFolder")?.ToString()
                          ?? key?.GetValue("InstallDir")?.ToString()
                          ?? key?.GetValue("InstallationPath")?.ToString();
            if (!string.IsNullOrWhiteSpace(install))
                yield return install;
        }

        using var currentUser = RegistryKey.OpenBaseKey(RegistryHive.CurrentUser, RegistryView.Default);
        using var userKey = currentUser.OpenSubKey(subKey);
        var userInstall = userKey?.GetValue("InstallFolder")?.ToString()
                          ?? userKey?.GetValue("InstallDir")?.ToString()
                          ?? userKey?.GetValue("InstallationPath")?.ToString();
        if (!string.IsNullOrWhiteSpace(userInstall))
            yield return userInstall;
    }
}