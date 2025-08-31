# 📡 PHP Broadcast Tool

This repository provides a simple PHP-based utility for sending broadcast messages to a Perfect World server (or compatible service).
It includes a CLI script and supporting packet parsing/encoding classes.

## 📂 Files

* **`broadcast.php`** – CLI script to send broadcast messages.
* **`packet_class.php`** – Defines `ReadPacket` and related classes for working with binary packets.

## 🚀 Usage

```bash
php broadcast.php <roleid> <channelid> <message>
```

* `<roleid>` – The role/player ID to identify the sender.
* `<channelid>` – The channel ID where the message will be broadcast.
* `<message>` – The actual text to send.

Example:

```bash
php broadcast.php 10001 3 "Hello PWCI community!"
```

## ⚙️ Configuration

By default, the script connects to:

* **Server Address:** `localhost`
* **Server Port:** `29300`

Edit the constants in `broadcast.php` if your server uses different values:

```php
const SERVER_ADDRESS = "localhost";
const SERVER_PORT    = 29300;
```

## 🛠 Requirements

* PHP 7.4+ (works with PHP 8.x)
* CLI access to run the script

## 📖 How It Works

1. `broadcast.php` builds a broadcast packet using `packet_class.php`.
2. The packet is sent to the configured server.
3. The server delivers the message to the specified channel.

## 📜 License

MIT License – feel free to use and modify.
