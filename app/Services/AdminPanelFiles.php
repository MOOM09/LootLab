<?php
final class AdminPanelFiles
{
    private string $root;
    private string $appUrl;
    public function __construct(string $root, string $appUrl)
    {
        $this->root = $root;
        $this->appUrl = rtrim($appUrl, "/");
    }
    public function upload(array $file): array
    {
        if (
            ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
            !is_string($file["tmp_name"] ?? null) ||
            !is_uploaded_file($file["tmp_name"])
        ) {
            throw new InvalidArgumentException("Choose a JPG, PNG or WebP image up to 5 MB.");
        }
        $path = $file["tmp_name"];
        $size = filesize($path);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
        $dimensions = @getimagesize($path);
        if (
            !$size ||
            $size > 5 * 1024 * 1024 ||
            !isset($extensions[$mime]) ||
            $dimensions === false ||
            max($dimensions[0], $dimensions[1]) > 4096 ||
            $dimensions[0] * $dimensions[1] > 12000000 ||
            ($dimensions["mime"] ?? "") !== $mime
        ) {
            throw new InvalidArgumentException(
                "Use a valid JPG, PNG or WebP up to 5 MB and 4096 pixels per side.",
            );
        }
        $directory = $this->root . "/public/uploads/admin";
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("The upload directory could not be created.");
        }
        $filename = bin2hex(random_bytes(16)) . "." . $extensions[$mime];
        if (!move_uploaded_file($path, $directory . "/" . $filename)) {
            throw new RuntimeException("The image could not be saved.");
        }
        return [
            "path" => "uploads/admin/" . $filename,
            "url" => $this->appUrl . "/uploads/admin/" . $filename,
        ];
    }
    public function avatar(int $id): string
    {
        $file = $this->root . "/storage/admin-panel/" . $id . ".json";
        if (!is_file($file)) {
            return "";
        }
        $data = json_decode((string) file_get_contents($file), true);
        $path = is_array($data) ? $data["avatar"] ?? "" : "";
        return is_string($path) && preg_match("~\Auploads/admin/[a-f0-9]{32}\.(?:png|jpg|webp)\z~", $path)
            ? $this->appUrl . "/" . $path
            : "";
    }
    public function saveAvatar(int $id, string $path): string
    {
        if (
            !preg_match("~\Auploads/admin/[a-f0-9]{32}\.(?:png|jpg|webp)\z~", $path) ||
            !is_file($this->root . "/public/" . $path)
        ) {
            throw new InvalidArgumentException("Upload your profile image first.");
        }
        $directory = $this->root . "/storage/admin-panel";
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException("Profile storage is not writable.");
        }
        $temporary = $directory . "/" . bin2hex(random_bytes(12)) . ".tmp";
        if (
            file_put_contents($temporary, json_encode(["avatar" => $path], JSON_THROW_ON_ERROR), LOCK_EX) ===
            false
        ) {
            throw new RuntimeException("The avatar could not be saved.");
        }
        if (!rename($temporary, $directory . "/" . $id . ".json")) {
            @unlink($temporary);
            throw new RuntimeException("The avatar could not be saved.");
        }
        return $this->avatar($id);
    }
}
