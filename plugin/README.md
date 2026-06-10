# VibeWarrior WordPress Plugin

Direct WordPress access for AI agents.

## What It Does

VibeWarrior runs PHP directly inside your WordPress process, giving AI agents full access to:

- WordPress functions (`get_posts`, `update_option`, `wp_insert_post`, ...)
- Database via `$wpdb`
- Filesystem (read, write, edit, delete files)
- WP-CLI commands
- Plugin ecosystem (all loaded plugins are available)

## Requirements

- WordPress 6.0+
- PHP 8.0+
- [WP MCP Adapter](https://github.com/use-wordpress/mcp-adapter) (bundled in release builds)

## Installation

### Release Build (Recommended)

1. Download the latest `.zip` from [Releases](https://github.com/Rankwisy/Vibeworriors/releases)
2. Upload via **Plugins → Add New → Upload Plugin**
3. Activate **VibeWarrior**
4. Go to **VibeWarrior → Connect** and enable AI abilities
5. Copy the MCP endpoint URL and configure your AI agent

### From Source

```bash
git clone https://github.com/Rankwisy/Vibeworriors.git
cd Vibeworriors/plugin
composer install
```

Then upload the `plugin/` directory to your WordPress `wp-content/plugins/vibewarrior/` folder.

## Connecting an AI Agent

### Claude Desktop

Add to `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "vibewarrior": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "https://yoursite.com/wp-json/mcp/vibewarrior"],
      "env": {
        "MCP_REMOTE_HEADER_AUTHORIZATION": "Basic BASE64_OF_USER:APP_PASSWORD"
      }
    }
  }
}
```

### Cursor / VS Code

```json
{
  "servers": {
    "vibewarrior": {
      "type": "http",
      "url": "https://yoursite.com/wp-json/mcp/vibewarrior",
      "headers": {
        "Authorization": "Basic BASE64_OF_USER:APP_PASSWORD"
      }
    }
  }
}
```

## Available Abilities

| Ability | Description |
|---|---|
| `vibewarrior/discover-abilities` | List all available tools |
| `vibewarrior/execute-php` | Execute PHP inside WordPress |
| `vibewarrior/read-file` | Read any file under ABSPATH |
| `vibewarrior/write-file` | Write files (PHP → sandbox only) |
| `vibewarrior/edit-file` | String-replace edit existing files |
| `vibewarrior/delete-file` | Delete files or directories |
| `vibewarrior/list-directory` | Browse the filesystem |
| `vibewarrior/disable-file` | Disable a sandbox plugin |
| `vibewarrior/enable-file` | Re-enable a sandbox plugin |
| `vibewarrior/run-wp-cli` | Run WP-CLI commands |
| `vibewarrior/get-wp-cli-job` | Poll async WP-CLI jobs |
| `vibewarrior/create-admin-access-link` | Generate one-time admin login URL |
| `vibewarrior/create-upload-link` | Generate signed file upload URL |

## Sandbox

AI-written PHP plugins are placed in `wp-content/vibewarrior-sandbox/`. If a file causes a fatal error:

1. The system auto-detects the crash and enters **safe mode**
2. All sandbox files are skipped (the MCP interface stays up)
3. Fix or delete the broken file via **VibeWarrior → Sandbox**
4. Click **Exit Safe Mode** to resume

## Security

⚠️ **This plugin is designed for development and staging environments only.**

Direct PHP execution gives AI the same power as a developer with shell access. Never enable on production unless you fully understand the implications.

## License

GPL-2.0-or-later

## Links

- Website: [vibewarrior.com](https://vibewarrior.com)
- GitHub: [github.com/Rankwisy/Vibeworriors](https://github.com/Rankwisy/Vibeworriors)
