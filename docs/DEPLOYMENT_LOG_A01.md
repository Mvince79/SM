# DEPLOYMENT LOG — TìmTrẻ.vn

## A-01 — Chuẩn bị VPS & Server Hardening

**Ngày thực hiện:** 09/08/2026
**Hạ tầng:** Google Cloud Compute Engine (thay vì Oracle Cloud như dự kiến ban đầu)
**Instance:** `instance-20260805-164717`
**Zone:** `us-east1-d`
**External IP:** `34.138.217.43`
**OS:** Ubuntu 24.04.4 LTS

### 1. Tạo user non-root có sudo

```bash
sudo adduser khang
sudo usermod -aG sudo khang
```

### 2. Khoá SSH — chỉ cho key, tắt password và root login

```bash
sudo sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
sudo sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo systemctl restart ssh   # lưu ý: Ubuntu 24.04 dùng tên service "ssh", không phải "sshd"
```

Verify: `sudo sshd -T | grep -E "permitrootlogin|passwordauthentication"` → `no` / `no`

### 3. Firewall (ufw) — chỉ mở 22, 80, 443

```bash
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

Verify: `sudo ufw status` → đúng 4 dòng (OpenSSH/22, 80, 443) × IPv4/IPv6, không port thừa.

⚠️ **Lưu ý cho A-02/A-04 sau này:** GCP còn có lớp VPC Firewall Rules riêng ngoài ufw — cần vào Console > VPC network > Firewall kiểm tra thêm, đảm bảo không có rule nào lỡ mở port 3306 (MySQL) hoặc 6379 (Redis) ra `0.0.0.0/0`.

### 4. fail2ban — chặn brute-force SSH

```bash
sudo apt install fail2ban -y   # đã có sẵn sẵn trong image, không cần cài mới
sudo bash -c 'cat > /etc/fail2ban/jail.local << EOF
[sshd]
enabled = true
port = 22
maxretry = 5
findtime = 600
bantime = 3600
EOF'
sudo systemctl enable fail2ban
sudo systemctl restart fail2ban
```

Verify: `sudo systemctl status fail2ban` → `active (running)`

### 5. Stack cơ bản

VM (image Google Cloud Marketplace) đã cài sẵn toàn bộ, không cần cài thêm:

| Phần mềm | Version |
|---|---|
| Nginx | 1.24.0 |
| PHP-FPM | 8.3.33 |
| MySQL | 8.0.46 |
| Redis | 7.0.15 (redis-cli ping → PONG) |
| Node.js | 20.20.2 |
| Composer | 2.7.1 |
| Python | 3.12.3 |

### 6. Swap & Timezone

- Swap 2GB đã có sẵn từ image (`free -h` → `Swap: 2.0Gi`), không cần tự tạo.
- Timezone: `sudo timedatectl set-timezone Asia/Ho_Chi_Minh` → verify `date` → `Sun Aug 9 20:10:22 +07 2026` ✅

### ⚠️ Ghi chú rủi ro cho các bước sau

RAM tổng chỉ **1.9Gi** — dưới mức khuyến nghị 4GB trong kế hoạch gốc. Cần theo dõi sát khi tới **A-04 (deploy AI service DeepFace)** vì model AI tốn RAM đáng kể lúc load — có thể cần giới hạn `--workers 1` cho uvicorn, hoặc cân nhắc nâng cấp VM nếu bị OOM-kill.

---

## Trạng thái: A-01 hoàn thành ✅ — sẵn sàng cho A-02 (Deploy Laravel + Nginx + SSL)
