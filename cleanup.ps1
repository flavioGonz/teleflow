
$lines = Get-Content "c:\Users\Flavio\Documents\EXPRESS\issabel\index.php"
# Remove lines 2839 to 3371 (1-indexed)
# In 0-indexed: 2838 to 3370
$newLines = @()
for ($i = 0; $i -lt $lines.Count; $i++) {
    if ($i -ge 2838 -and $i -le 3370) {
        continue
    }
    $newLines += $lines[$i]
}
$newLines | Out-File "c:\Users\Flavio\Documents\EXPRESS\issabel\index.php" -Encoding utf8
