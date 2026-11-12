using System.Net.Http;
using System.Security.Cryptography;

namespace VirtualBoostLauncher;

public static class ClientInstaller
{
    public const string Version = "1.7.0";
    public const string InstallerUrl = "https://github.com/openmultiplayer/launcher/releases/download/v1.7.0/omp-launcher-setup.exe";
    private const string ExpectedSha256 = "465b10a20c5c243f120f83bed49128804e22103786d3462d54cf3ebd89657a1e";

    private static readonly HttpClient Http = new()
    {
        Timeout = TimeSpan.FromMinutes(5)
    };

    public static async Task<string> DownloadOpenMpInstallerAsync(
        IProgress<int>? progress = null,
        CancellationToken cancellationToken = default)
    {
        var destination = Path.Combine(
            Path.GetTempPath(),
            $"VirtualBoost-openmp-setup-{Version}.exe");

        using var response = await Http.GetAsync(
            InstallerUrl,
            HttpCompletionOption.ResponseHeadersRead,
            cancellationToken);
        response.EnsureSuccessStatusCode();

        var totalBytes = response.Content.Headers.ContentLength;
        await using (var source = await response.Content.ReadAsStreamAsync(cancellationToken))
        await using (var target = new FileStream(destination, FileMode.Create, FileAccess.Write, FileShare.None))
        {
            var buffer = new byte[81920];
            long received = 0;
            int read;

            while ((read = await source.ReadAsync(buffer.AsMemory(0, buffer.Length), cancellationToken)) > 0)
            {
                await target.WriteAsync(buffer.AsMemory(0, read), cancellationToken);
                received += read;

                if (totalBytes is > 0)
                    progress?.Report((int)Math.Clamp(received * 100 / totalBytes.Value, 0, 100));
            }
        }

        await using var downloadedFile = File.OpenRead(destination);
        var hash = Convert.ToHexString(await SHA256.HashDataAsync(downloadedFile, cancellationToken))
            .ToLowerInvariant();

        if (!hash.Equals(ExpectedSha256, StringComparison.OrdinalIgnoreCase))
        {
            File.Delete(destination);
            throw new InvalidDataException("O instalador baixado não passou na verificação de integridade SHA-256. O arquivo foi descartado.");
        }

        progress?.Report(100);
        return destination;
    }
}
