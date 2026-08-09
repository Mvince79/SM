#!/usr/bin/env bash
#
# scripts/backup.sh — A-06: Backup tự động Database + Storage (TìmTrẻ.vn)
#
# Backup database (mysqldump, nén gzip) + storage/app/public (ảnh tin đăng)
# và storage/app/private (ảnh KYC — đã mã hoá sẵn từ Sprint 3.5, backup giữ
# nguyên trạng thái mã hoá, KHÔNG giải mã ra rồi backup plaintext).
#
# Lưu ngoài webroot, giữ lại 7 bản gần nhất, tự xoá bản cũ hơn.
#
# Chạy tay:   bash scripts/backup.sh
# Chạy cron:  xem /etc/cron.d/timtre-backup đi kèm

set -euo pipefail

# ── Cấu hình ─────────────────────────────────────────────────────────
APP_ROOT="/var/www/timtre"                 # ⚠️ chỉnh lại đúng path thật trên VPS
ENV_FILE="${APP_ROOT}/.env"
BACKUP_ROOT="/var/backups/timtre"          # ngoài webroot, không public được
KEEP_DAYS=7
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="/var/log/timtre-backup.log"

mkdir -p "${BACKUP_ROOT}/db" "${BACKUP_ROOT}/storage"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "${LOG_FILE}"
}

# ── Đọc DB credentials từ .env (không hardcode secrets trong script) ──
if [[ ! -f "${ENV_FILE}" ]]; then
    log "LỖI: Không tìm thấy .env tại ${ENV_FILE}"
    exit 1
fi

get_env() {
    grep -E "^${1}=" "${ENV_FILE}" | tail -n1 | cut -d '=' -f2- | tr -d '"'
}

DB_HOST="$(get_env DB_HOST)"
DB_PORT="$(get_env DB_PORT)"
DB_DATABASE="$(get_env DB_DATABASE)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

# ── 1. Backup database ─────────────────────────────────────────────────
DB_BACKUP_FILE="${BACKUP_ROOT}/db/db_${TIMESTAMP}.sql.gz"

log "Bắt đầu backup database '${DB_DATABASE}'..."

MYSQL_PWD="${DB_PASSWORD}" mysqldump \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    "${DB_DATABASE}" | gzip > "${DB_BACKUP_FILE}"

if [[ -s "${DB_BACKUP_FILE}" ]]; then
    log "OK: database backup -> ${DB_BACKUP_FILE} ($(du -h "${DB_BACKUP_FILE}" | cut -f1))"
else
    log "LỖI: file backup database rỗng hoặc không tạo được"
    rm -f "${DB_BACKUP_FILE}"
    exit 1
fi

# ── 2. Backup storage (public + private/KYC, giữ nguyên mã hoá) ───────
STORAGE_BACKUP_FILE="${BACKUP_ROOT}/storage/storage_${TIMESTAMP}.tar.gz"

log "Bắt đầu backup storage/app..."

tar -czf "${STORAGE_BACKUP_FILE}" \
    -C "${APP_ROOT}" \
    storage/app/public \
    storage/app/private \
    2>>"${LOG_FILE}" || {
        log "CẢNH BÁO: 1 trong 2 thư mục storage có thể không tồn tại, kiểm tra log phía trên";
    }

if [[ -s "${STORAGE_BACKUP_FILE}" ]]; then
    log "OK: storage backup -> ${STORAGE_BACKUP_FILE} ($(du -h "${STORAGE_BACKUP_FILE}" | cut -f1))"
else
    log "LỖI: file backup storage rỗng hoặc không tạo được"
    exit 1
fi

# ── 3. Xoay vòng — giữ lại KEEP_DAYS bản gần nhất, xoá bản cũ hơn ──────
log "Dọn dẹp backup cũ hơn ${KEEP_DAYS} ngày..."

find "${BACKUP_ROOT}/db" -name "db_*.sql.gz" -mtime +"${KEEP_DAYS}" -print -delete | tee -a "${LOG_FILE}"
find "${BACKUP_ROOT}/storage" -name "storage_*.tar.gz" -mtime +"${KEEP_DAYS}" -print -delete | tee -a "${LOG_FILE}"

log "Hoàn tất backup ${TIMESTAMP}."
