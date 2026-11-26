param(
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$Username,

    [Parameter(Mandatory = $true, Position = 1)]
    [string]$Name
)

$securePassword = Read-Host "Contraseña (mínimo 12 caracteres)" -AsSecureString
$passwordPointer = [IntPtr]::Zero

try {
    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)

    if ([Text.Encoding]::UTF8.GetByteCount($password) -lt 12) {
        throw "La contraseña debe tener al menos 12 caracteres."
    }

    $encodedPassword = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($password))
    $encodedPassword | & php (Join-Path $PSScriptRoot "create-user.php") $Username $Name "--password-base64-stdin"
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
} finally {
    if ($passwordPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }
    if ($securePassword) {
        $securePassword.Dispose()
    }
    $password = $null
    $encodedPassword = $null
}
