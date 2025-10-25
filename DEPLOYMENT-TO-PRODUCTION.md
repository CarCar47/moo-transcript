# Plugin Deployment Guide - Transcript Grade Report

## Repository Architecture

This Moodle 5.1 deployment uses a **two-repository system** for managing the transcript plugin:

### 1. **Main Repository (lms-VM)**
- **Location:** `C:\Users\c_clo\OneDrive\Personal\Coding\cor4edu-sms\moodle-VM`
- **GitHub:** https://github.com/CarCar47/lms-VM
- **Purpose:** Complete Moodle 5.1 system with all core files and plugins
- **Production VM:** Deployed to `/var/www/html/` on moodle-vm-demo (sms-edu-47)

### 2. **Standalone Plugin Repository (moo-transcript)**
- **Location:** `C:\Users\c_clo\OneDrive\Personal\Coding\transcript`
- **GitHub:** https://github.com/CarCar47/moo-transcript
- **Purpose:** Plugin development and version control
- **Plugin Type:** Grade Report (`gradereport_transcript`)

## Why Two Repositories?

- **Easier Development:** Work on plugin in isolation without full Moodle system
- **Version Control:** Separate plugin versioning from Moodle system
- **Reusability:** Plugin can be shared/installed in other Moodle instances
- **Testing:** Test plugin independently before integrating into main system

## Moodle 5.1 Directory Structure

Moodle 5.1 introduced a NEW directory structure with `/public/` subdirectory:

```
/var/www/html/                    ← Root (config.php, index.php redirector)
├── config.php                    ← Moodle configuration
├── index.php                     ← Redirects to /public/
├── lib/setup.php                 ← Minimal setup file
└── public/                       ← ACTUAL MOODLE CODE HERE
    ├── admin/
    ├── grade/
    │   └── report/               ← Grade report plugins
    │       ├── grader/
    │       ├── history/
    │       └── transcript/       ← OUR PLUGIN LOCATION
    ├── lib/
    └── version.php               ← Moodle version
```

**CRITICAL:** The transcript plugin MUST be at:
```
/var/www/html/public/grade/report/transcript/
```

NOT at `/var/www/html/grade/report/transcript/` (wrong - doesn't exist in Moodle 5.1)

## Production Environment

- **Google Cloud Project:** sms-edu-47
- **VM Name:** moodle-vm-demo
- **Zone:** us-central1-a
- **Moodle Root:** `/var/www/html/`
- **Plugin Path:** `/var/www/html/public/grade/report/transcript/`
- **Moodle URL:** https://lms.cor4edu.us
- **Database:** MariaDB (moodle_lms)

## ⚠️ CRITICAL - Pre-Deployment Backup Checklist

**STOP! Before deploying ANY plugin update to production, you MUST complete this backup checklist.**

Data loss occurred on October 25, 2025 because backups were not taken before triggering the database upgrade. Following Moodle's official best practices, you must backup **3 components** before every production deployment:

### Why Backups Are Critical

- **Moodle upgrades modify the database schema** - If something goes wrong, you cannot undo without a backup
- **Plugin data includes user records, grades, transcripts** - Loss is permanent without backup
- **Production downtime is costly** - Fast rollback requires pre-existing backups
- **Official Moodle documentation mandates it** - "Do not risk what you cannot afford to lose"

### The 3-Part Backup (Complete ALL Before Deployment)

#### 1. Database Backup (MariaDB)

```bash
# SSH into production VM
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a

# Create timestamped database backup
sudo mysqldump -u moodle_user -p'YOUR_DB_PASSWORD' \
  -C -Q -e --create-options moodle_lms \
  > ~/backups/moodle_lms_$(date +%Y%m%d_%H%M%S).sql

# Compress the backup (saves space)
gzip ~/backups/moodle_lms_$(date +%Y%m%d_%H%M%S).sql
```

**Verify backup was created:**
```bash
ls -lh ~/backups/moodle_lms_*.sql.gz
# Should show file with today's date and size > 0 bytes
```

#### 2. Moodledata Backup (User Files, Cache, Sessions)

```bash
# Create timestamped moodledata backup
sudo tar -czf ~/backups/moodledata_$(date +%Y%m%d_%H%M%S).tar.gz \
  -C /var/moodledata .

# IMPORTANT: Exclude cache directory to save space (optional but recommended)
sudo tar -czf ~/backups/moodledata_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude='cache' --exclude='sessions' --exclude='temp' \
  -C /var/moodledata .
```

**Verify backup was created:**
```bash
ls -lh ~/backups/moodledata_*.tar.gz
# Should show file with today's date
```

#### 3. Moodle Code Backup (Plugin Files)

```bash
# Backup ONLY the transcript plugin (faster)
sudo tar -czf ~/backups/transcript_plugin_$(date +%Y%m%d_%H%M%S).tar.gz \
  -C /var/www/html/public/grade/report/transcript .

# OR backup entire Moodle public directory (slower but complete)
sudo tar -czf ~/backups/moodle_public_$(date +%Y%m%d_%H%M%S).tar.gz \
  -C /var/www/html/public .
```

**Verify backup was created:**
```bash
ls -lh ~/backups/transcript_plugin_*.tar.gz
# Should show file with today's date
```

### Backup Storage Best Practices

1. **Keep backups in separate location** - Store on different server or download locally
2. **Retention policy** - Keep at least last 3 backups (daily backups for 3 days)
3. **Test restore process** - Verify you can actually restore from backup (test in staging)
4. **Download critical backups locally**:
   ```bash
   # Download database backup to your local machine
   gcloud compute scp moodle-vm-demo:~/backups/moodle_lms_YYYYMMDD_HHMMSS.sql.gz \
     C:/Users/c_clo/backups/ --project=sms-edu-47 --zone=us-central1-a
   ```

### Quick Backup Script (Run Before Every Deployment)

```bash
#!/bin/bash
# Save this as ~/backup_before_upgrade.sh on production VM

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=~/backups
mkdir -p $BACKUP_DIR

echo "🔄 Starting pre-deployment backup - $TIMESTAMP"

# 1. Database backup
echo "📊 Backing up database..."
sudo mysqldump -u moodle_user -p'YOUR_DB_PASSWORD' \
  -C -Q -e --create-options moodle_lms \
  | gzip > $BACKUP_DIR/moodle_lms_$TIMESTAMP.sql.gz

# 2. Moodledata backup (excluding cache)
echo "📁 Backing up moodledata..."
sudo tar -czf $BACKUP_DIR/moodledata_$TIMESTAMP.tar.gz \
  --exclude='cache' --exclude='sessions' --exclude='temp' \
  -C /var/moodledata .

# 3. Plugin code backup
echo "🔌 Backing up transcript plugin..."
sudo tar -czf $BACKUP_DIR/transcript_plugin_$TIMESTAMP.tar.gz \
  -C /var/www/html/public/grade/report/transcript .

echo "✅ Backup complete! Files created:"
ls -lh $BACKUP_DIR/*$TIMESTAMP*

echo ""
echo "⚠️  IMPORTANT: Download these backups locally before proceeding with deployment"
```

**Usage:**
```bash
chmod +x ~/backup_before_upgrade.sh
~/backup_before_upgrade.sh
```

---

## 🔒 Maintenance Mode Requirements

**CRITICAL:** Always enable Maintenance Mode before deploying plugin updates that require database changes.

### Why Maintenance Mode Is Required

- **Prevents data corruption** - Users cannot add/modify data while database schema is changing
- **Prevents transaction conflicts** - No concurrent writes during upgrade
- **Official Moodle requirement** - Documented in Moodle upgrade best practices
- **Administrators can still login** - You can still access admin panel in maintenance mode

### Enable Maintenance Mode (Before Step 5 Deployment)

**Option A: Via Admin UI (Recommended)**
1. Login to https://lms.cor4edu.us as admin
2. Navigate to: **Site Administration → Server → Maintenance mode**
3. Check "Enable maintenance mode"
4. Add message: "System upgrade in progress. Site will be back online shortly."
5. Click "Save changes"

**Option B: Via CLI**
```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="php /var/www/html/admin/cli/maintenance.php --enable"
```

### Verify Maintenance Mode Is Active

```bash
# Check via CLI
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="php /var/www/html/admin/cli/maintenance.php --status"

# Expected output: "Maintenance mode is currently enabled"
```

**Visual verification:**
- Visit https://lms.cor4edu.us (logged out)
- Should see maintenance mode message
- Admin login still works at https://lms.cor4edu.us/login/

### Disable Maintenance Mode (After Step 7 Upgrade Complete)

**Option A: Via Admin UI**
1. Site Administration → Server → Maintenance mode
2. Uncheck "Enable maintenance mode"
3. Click "Save changes"

**Option B: Via CLI**
```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="php /var/www/html/admin/cli/maintenance.php --disable"
```

---

## Complete Deployment Workflow

### 📋 Pre-Deployment Safety Checklist (Complete Before Starting)

Before you begin ANY deployment, verify you have completed these safety requirements:

- [ ] **Read the backup section above** - Understand 3-part backup process
- [ ] **Read maintenance mode section** - Know how to enable/disable it
- [ ] **Read recovery procedures** - Know how to restore if something goes wrong
- [ ] **Read common mistakes** - Understand what NOT to do
- [ ] **Have database password ready** - For backup commands
- [ ] **Have local backup destination ready** - C:/Users/c_clo/backups/ exists
- [ ] **Tested backup script** - Verify it runs successfully
- [ ] **Tested restore script** - Verify you can recover from backup (in staging)

**If you answered "No" to ANY item above, STOP and complete it first.**

### Deployment Steps Overview

The deployment process has **7 steps + backup/maintenance requirements**:

1. **Develop** - Make changes in standalone repository
2. **Commit** - Push to standalone plugin repository (GitHub)
3. **Sync** - Copy to main moodle-VM repository
4. **Commit** - Push to main repository (GitHub)
5. **🔒 BACKUP** - Complete 3-part backup (MANDATORY)
6. **🔒 MAINTENANCE MODE** - Enable before deploying files (MANDATORY)
7. **Deploy** - Upload plugin files to production VM
8. **Purge Cache** - Clear Moodle cache
9. **Verify Backups** - Confirm backups exist before upgrade
10. **Upgrade** - User triggers database upgrade (if version changed)
11. **🔒 DISABLE MAINTENANCE MODE** - Only after verifying success

---

### Step 1: Develop in Standalone Repository
```bash
cd C:\Users\c_clo\OneDrive\Personal\Coding\transcript
# Make your changes to plugin files
# Test locally if possible
```

### Step 2: Commit to Standalone Plugin Repository
```bash
cd C:\Users\c_clo\OneDrive\Personal\Coding\transcript
git add .
git commit -m "feat: Your feature description"
git push origin master
```

### Step 3: Sync to Main Moodle Repository
```bash
# Remove .git from plugin folder if it exists (prevents nested repos)
rm -rf "C:/Users/c_clo/OneDrive/Personal/Coding/cor4edu-sms/moodle-VM/public/grade/report/transcript/.git"

# Copy all plugin files
cp -r "C:/Users/c_clo/OneDrive/Personal/Coding/transcript/"* \
      "C:/Users/c_clo/OneDrive/Personal/Coding/cor4edu-sms/moodle-VM/public/grade/report/transcript/"

# Clean up temp files
rm -f "C:/Users/c_clo/OneDrive/Personal/Coding/cor4edu-sms/moodle-VM/public/grade/report/transcript/transcript.tar.gz"
rm -f "C:/Users/c_clo/OneDrive/Personal/Coding/cor4edu-sms/moodle-VM/public/grade/report/transcript/nul"
```

### Step 4: Commit to Main Repository
```bash
cd C:\Users\c_clo\OneDrive\Personal\Coding\cor4edu-sms\moodle-VM
git add public/grade/report/transcript/
git commit -m "feat: Update transcript plugin to vX.X.X"
git push origin main
```

### Step 5a: 🔒 CREATE BACKUPS (MANDATORY - DO NOT SKIP!)

**⚠️ CRITICAL: Complete 3-part backup BEFORE deploying files to production.**

Follow the backup procedures documented in "Pre-Deployment Backup Checklist" section above.

**Quick backup command:**
```bash
# SSH into production VM
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a

# Run automated backup script
~/backup_before_upgrade.sh

# Download backups locally for extra safety
exit  # Exit SSH

# Download database backup to local machine
gcloud compute scp moodle-vm-demo:~/backups/moodle_lms_$(date +%Y%m%d)*.sql.gz \
  C:/Users/c_clo/backups/ --project=sms-edu-47 --zone=us-central1-a
```

**Verify backups created successfully before proceeding!**

---

### Step 5b: 🔒 ENABLE MAINTENANCE MODE (MANDATORY)

**⚠️ CRITICAL: Enable maintenance mode BEFORE deploying plugin files.**

```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="php /var/www/html/admin/cli/maintenance.php --enable"

# Verify maintenance mode is active
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="php /var/www/html/admin/cli/maintenance.php --status"

# Expected output: "Maintenance mode is currently enabled"
```

**Do not proceed until maintenance mode is confirmed active!**

---

### Step 5c: Deploy Plugin Files to Production VM
```bash
# Option A: Deploy entire moodle-VM (if other changes exist)
cd C:\Users\c_clo\OneDrive\Personal\Coding\cor4edu-sms\moodle-VM
tar -czf moodle-vm.tar.gz --exclude='.git' .
gcloud compute scp moodle-vm.tar.gz moodle-vm-demo:/tmp/ --project=sms-edu-47 --zone=us-central1-a
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="cd /tmp && tar -xzf moodle-vm.tar.gz && sudo rsync -av public/ /var/www/html/public/ && sudo chown -R www-data:www-data /var/www/html/"

# Option B: Deploy ONLY transcript plugin (faster)
cd C:\Users\c_clo\OneDrive\Personal\Coding\transcript
tar -czf transcript.tar.gz --exclude='.git' --exclude='*.tar.gz' --exclude='nul' .
gcloud compute scp transcript.tar.gz moodle-vm-demo:/tmp/ --project=sms-edu-47 --zone=us-central1-a
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="mkdir -p /tmp/transcript_deploy && cd /tmp/transcript_deploy && tar -xzf /tmp/transcript.tar.gz && sudo rsync -av ./ /var/www/html/public/grade/report/transcript/ && sudo chown -R www-data:www-data /var/www/html/public/grade/report/transcript && cd /tmp && rm -rf /tmp/transcript_deploy /tmp/transcript.tar.gz"
```

### Step 6: CRITICAL - Purge Moodle Cache
```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="php /var/www/html/admin/cli/purge_caches.php"
```

**WHY THIS IS CRITICAL:**
- Moodle caches plugin versions in `/var/moodledata/cache/core_component.php`
- Without purging, Moodle won't detect the new version
- User won't see upgrade notification
- Database migration won't run

### Step 7: User Triggers Database Upgrade (AUTOMATIC)

**⚠️ CRITICAL: Before user clicks upgrade, verify all backups completed successfully!**

```bash
# Verify backups exist and are recent (created today)
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="ls -lh ~/backups/*$(date +%Y%m%d)* 2>/dev/null || echo 'ERROR: No backups found for today!'"

# Expected: Should list 3 backup files (database, moodledata, plugin code)
```

**If backups don't exist, STOP and create them before proceeding!**

---

**THE USER DOES THIS - NOT YOU:**

1. **VERIFY BACKUPS COMPLETE** - Check that backups exist before proceeding
2. User logs into https://lms.cor4edu.us as admin
3. **Moodle automatically detects** version change from `version.php`
4. **Moodle forces upgrade notification** - user cannot bypass this
5. User clicks "Upgrade Moodle database now"
6. Moodle runs `db/upgrade.php` automatically
7. Database columns/tables created
8. Success message displayed
9. **Disable maintenance mode** (see Maintenance Mode section above)

**CRITICAL - What NOT to Do:**
- ❌ **NEVER uninstall/reinstall the plugin** - This deletes ALL plugin data permanently
- ❌ **NEVER force database upgrades** via CLI (`php admin/cli/upgrade.php`) - Doesn't work for plugins
- ❌ **NEVER run SQL migrations manually** - Use Moodle's XMLDB upgrade system only
- ❌ **NEVER skip backup verification** - If upgrade fails without backup, data is lost forever
- ❌ **NEVER assume upgrade happened** without user confirmation

**If Upgrade Fails:**
1. **DO NOT PANIC** - You have backups
2. **DO NOT retry** immediately - Check error logs first
3. **Follow Data Loss Recovery Procedures** below to restore from backup
4. **Review error logs** to identify root cause before retrying

## How Moodle Detects Plugin Changes

Moodle compares:
1. **File version** from `version.php`: `$plugin->version = 2025102207;`
2. **Database version** from `mdl_config_plugins` table

If file version > database version:
- Moodle displays upgrade notification (cannot be dismissed)
- User clicks upgrade
- Moodle runs `db/upgrade.php` with XMLDB API
- Database version updated to match file version

## Verification Steps

### 1. Verify Files Deployed
```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="cat /var/www/html/public/grade/report/transcript/version.php | grep version"
```

Expected output:
```php
$plugin->version   = 2025102207;  // Or current version number
```

### 2. Verify Database Version (After User Upgrades)
```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="mariadb -u moodle_user -p'PASSWORD' moodle_lms -e \"SELECT name, value FROM mdl_config_plugins WHERE plugin='gradereport_transcript' AND name='version';\""
```

Should match file version.

### 3. Verify Database Columns Created
```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="mariadb -u moodle_user -p'PASSWORD' moodle_lms -e \"DESCRIBE mdl_gradereport_transcript_requests;\""
```

## 🆘 Data Loss Recovery Procedures

**If the plugin upgrade fails or causes data loss, follow these steps to restore from backup.**

### When to Use Recovery Procedures

- Database upgrade failed with errors
- Plugin data is missing or corrupted after upgrade
- Moodle becomes unusable after plugin deployment
- User accidentally uninstalled plugin instead of upgrading
- Need to rollback to previous plugin version

### Recovery Steps (Complete in Order)

#### Step 1: Enable Maintenance Mode Immediately

```bash
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a \
  --command="php /var/www/html/admin/cli/maintenance.php --enable"
```

**Why:** Prevents users from accessing corrupted data and prevents further damage.

#### Step 2: Identify Most Recent Backup

```bash
# SSH into production VM
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a

# List all backups sorted by date (most recent first)
ls -lt ~/backups/

# Identify the 3 backup files created just before the failed upgrade:
# - moodle_lms_YYYYMMDD_HHMMSS.sql.gz (database)
# - moodledata_YYYYMMDD_HHMMSS.tar.gz (user files)
# - transcript_plugin_YYYYMMDD_HHMMSS.tar.gz (plugin code)
```

#### Step 3: Restore Database (Most Critical)

```bash
# Decompress the database backup
gunzip ~/backups/moodle_lms_YYYYMMDD_HHMMSS.sql.gz

# Drop and recreate the database (DESTRUCTIVE - make sure you have backup!)
sudo mariadb -u root -p <<EOF
DROP DATABASE moodle_lms;
CREATE DATABASE moodle_lms DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON moodle_lms.* TO 'moodle_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Restore database from backup
sudo mariadb -u moodle_user -p'YOUR_DB_PASSWORD' moodle_lms < ~/backups/moodle_lms_YYYYMMDD_HHMMSS.sql

# Verify restoration
sudo mariadb -u moodle_user -p'YOUR_DB_PASSWORD' moodle_lms \
  -e "SELECT COUNT(*) FROM mdl_user; SELECT * FROM mdl_config_plugins WHERE plugin='gradereport_transcript' AND name='version';"
```

**Expected output:**
- User count should match pre-upgrade count
- Plugin version should show the OLD version number (before upgrade)

#### Step 4: Restore Plugin Code

```bash
# Backup the current (broken) plugin code
sudo mv /var/www/html/public/grade/report/transcript \
     /var/www/html/public/grade/report/transcript.broken.$(date +%Y%m%d_%H%M%S)

# Restore plugin code from backup
sudo mkdir -p /var/www/html/public/grade/report/transcript
sudo tar -xzf ~/backups/transcript_plugin_YYYYMMDD_HHMMSS.tar.gz \
     -C /var/www/html/public/grade/report/transcript

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/public/grade/report/transcript
```

#### Step 5: Restore Moodledata (If Needed)

**⚠️ Only restore moodledata if user files were corrupted. Usually not needed for plugin upgrades.**

```bash
# Backup current moodledata
sudo mv /var/moodledata /var/moodledata.broken.$(date +%Y%m%d_%H%M%S)

# Restore moodledata from backup
sudo mkdir -p /var/moodledata
sudo tar -xzf ~/backups/moodledata_YYYYMMDD_HHMMSS.tar.gz \
     -C /var/moodledata

# Fix permissions
sudo chown -R www-data:www-data /var/moodledata
sudo chmod -R 0755 /var/moodledata
```

#### Step 6: Purge Moodle Cache

```bash
# Clear all caches to force Moodle to reload plugin
php /var/www/html/admin/cli/purge_caches.php
```

#### Step 7: Verify Restoration

```bash
# Check plugin version in files
cat /var/www/html/public/grade/report/transcript/version.php | grep version

# Check plugin version in database
sudo mariadb -u moodle_user -p'YOUR_DB_PASSWORD' moodle_lms \
  -e "SELECT name, value FROM mdl_config_plugins WHERE plugin='gradereport_transcript';"

# Check error log for any issues
tail -50 /var/moodledata/error.log
```

**Expected:**
- File version and database version should match (pre-upgrade version)
- No errors in error.log
- Plugin should be functional

#### Step 8: Test Plugin Functionality

1. Login to https://lms.cor4edu.us as admin
2. Navigate to plugin pages (transcript management, transfer credits, etc.)
3. Verify data is present and correct
4. Test generating a transcript for a student
5. Verify all data matches pre-upgrade state

#### Step 9: Disable Maintenance Mode (Only If Restoration Successful)

```bash
php /var/www/html/admin/cli/maintenance.php --disable
```

### Post-Recovery Actions

1. **Document what went wrong** - Add notes to version history section
2. **Identify root cause** - Review error logs to understand why upgrade failed
3. **Fix the issue** - Update plugin code to address the problem
4. **Test in staging** - Deploy to test environment before trying production again
5. **Create fresh backups** - Before attempting upgrade again

### Quick Recovery Script

```bash
#!/bin/bash
# Save this as ~/emergency_restore.sh on production VM
# Usage: ./emergency_restore.sh YYYYMMDD_HHMMSS

if [ -z "$1" ]; then
  echo "Usage: $0 TIMESTAMP"
  echo "Example: $0 20251025_143022"
  exit 1
fi

TIMESTAMP=$1
BACKUP_DIR=~/backups

echo "🆘 EMERGENCY RESTORE - Timestamp: $TIMESTAMP"
echo ""

# Step 1: Enable maintenance mode
echo "🔒 Enabling maintenance mode..."
php /var/www/html/admin/cli/maintenance.php --enable

# Step 2: Restore database
echo "📊 Restoring database..."
gunzip -k $BACKUP_DIR/moodle_lms_$TIMESTAMP.sql.gz
sudo mariadb -u moodle_user -p'YOUR_DB_PASSWORD' moodle_lms < $BACKUP_DIR/moodle_lms_$TIMESTAMP.sql

# Step 3: Restore plugin code
echo "🔌 Restoring plugin code..."
sudo mv /var/www/html/public/grade/report/transcript \
     /var/www/html/public/grade/report/transcript.broken.$(date +%Y%m%d_%H%M%S)
sudo mkdir -p /var/www/html/public/grade/report/transcript
sudo tar -xzf $BACKUP_DIR/transcript_plugin_$TIMESTAMP.tar.gz \
     -C /var/www/html/public/grade/report/transcript
sudo chown -R www-data:www-data /var/www/html/public/grade/report/transcript

# Step 4: Purge cache
echo "🗑️ Purging caches..."
php /var/www/html/admin/cli/purge_caches.php

echo ""
echo "✅ Restore complete! Verify functionality before disabling maintenance mode."
echo "   To disable maintenance mode: php /var/www/html/admin/cli/maintenance.php --disable"
```

---

## ❌ Common Mistakes That Cause Data Loss

**Learn from these mistakes to prevent future data loss incidents.**

### Mistake #1: Uninstalling Plugin Instead of Upgrading ⚠️ MOST COMMON

**What Happened:**
- User went to Site Administration → Plugins → Plugin overview
- Clicked "Uninstall" on gradereport_transcript
- Thought they could reinstall with new version
- **ALL plugin data deleted permanently** (transcripts, transfer credits, grades, requests)

**Why It's Wrong:**
- Moodle's "Uninstall" button **DROPS ALL PLUGIN TABLES** from database
- Data deletion is immediate and permanent
- Cannot be undone without database backup

**Correct Way:**
- NEVER uninstall plugins to upgrade them
- Simply deploy new plugin files and bump version number
- Moodle detects version change and runs upgrade.php
- Data is preserved, only schema changes are applied

**Prevention:**
- Remove "Uninstall" button visibility for production admins
- Add warning signs near plugin management pages
- Always use deployment workflow in this document

### Mistake #2: No Backups Before Upgrade

**What Happened:**
- Deployed plugin update to production
- User clicked "Upgrade Moodle database now"
- Upgrade failed with database error
- No backups exist - data cannot be recovered

**Why It's Wrong:**
- Database upgrades modify schema and can corrupt data
- Moodle doesn't create automatic backups during upgrades
- Without backup, failed upgrade means permanent data loss

**Correct Way:**
- ALWAYS complete 3-part backup before deployment (database, moodledata, code)
- Verify backups exist before user clicks upgrade
- Test backups can be restored (in staging environment)

**Prevention:**
- Make backup checklist mandatory (see section above)
- Download backups locally before triggering upgrade
- Use automated backup script

### Mistake #3: Skipping Maintenance Mode

**What Happened:**
- Deployed plugin update without enabling maintenance mode
- Users were adding data while upgrade was running
- Database transaction conflicts caused data corruption
- Some records lost, some partially written

**Why It's Wrong:**
- Moodle allows users to write to database during upgrade
- Concurrent writes to tables being modified = data corruption
- No locking mechanism to prevent conflicts

**Correct Way:**
- Enable maintenance mode BEFORE deploying plugin files
- Keep maintenance mode enabled through entire upgrade process
- Disable maintenance mode only after verifying upgrade success

**Prevention:**
- Add maintenance mode to deployment checklist
- Verify maintenance mode is active before proceeding
- Monitor error logs during upgrade for transaction conflicts

### Mistake #4: Manual Database Modifications

**What Happened:**
- Tried to "help" the upgrade by manually adding columns via SQL
- Used phpMyAdmin or mariadb CLI to ALTER TABLE
- Moodle's upgrade.php still ran, creating duplicate/conflicting schema
- Plugin failed to load due to schema mismatch

**Why It's Wrong:**
- Moodle's XMLDB system expects to control all schema changes
- Manual changes bypass upgrade version tracking
- Can cause version number mismatches and failed upgrades

**Correct Way:**
- ONLY use db/upgrade.php for schema changes
- Let Moodle's upgrade system handle all database modifications
- Trust the XMLDB framework - it's database-neutral and tested

**Prevention:**
- Never touch production database manually
- All schema changes must go through upgrade.php
- Use XMLDB Editor to generate upgrade code

### Mistake #5: Forcing CLI Upgrades for Plugins

**What Happened:**
- User didn't see upgrade notification in admin UI
- Tried to force upgrade: `php admin/cli/upgrade.php`
- CLI upgrade ran but didn't process plugin upgrades correctly
- Database schema out of sync with plugin code

**Why It's Wrong:**
- `admin/cli/upgrade.php` is designed for Moodle CORE upgrades
- Plugin upgrades should be triggered through admin UI
- CLI doesn't always process plugin savepoints correctly

**Correct Way:**
- Purge cache if upgrade notification doesn't appear
- Let user trigger upgrade through admin web interface
- CLI is for core Moodle, not plugins

**Prevention:**
- Document correct upgrade trigger method
- Don't use CLI upgrade commands for plugins
- Trust Moodle's automatic detection system

---

## Troubleshooting

### Issue: Moodle Not Detecting New Version

**Symptoms:**
- User logs in, no upgrade notification appears
- Database version doesn't match file version

**Solutions:**
1. **Purge cache** (most common fix):
   ```bash
   php /var/www/html/admin/cli/purge_caches.php
   ```

2. **Check file deployed to correct location**:
   ```bash
   # CORRECT location
   ls -la /var/www/html/public/grade/report/transcript/version.php

   # WRONG location (shouldn't exist)
   ls -la /var/www/html/grade/report/transcript/version.php
   ```

3. **Check file permissions**:
   ```bash
   # Should be www-data:www-data
   ls -la /var/www/html/public/grade/report/transcript/

   # Fix if needed
   sudo chown -R www-data:www-data /var/www/html/public/grade/report/transcript/
   ```

4. **Verify PHP syntax**:
   ```bash
   php -l /var/www/html/public/grade/report/transcript/version.php
   ```

### Issue: Deployed to Wrong Location

**Symptoms:**
- Files exist at `/var/www/html/grade/report/transcript/`
- Moodle doesn't detect plugin

**Solution:**
```bash
# Remove wrong location
sudo rm -rf /var/www/html/grade/

# Redeploy to correct location
# (Follow Step 5 above)
```

### Issue: Upgrade Runs But Columns Not Created

**Symptoms:**
- Upgrade notification appeared
- User clicked upgrade
- But database columns missing

**Solutions:**
1. Check upgrade.php for errors:
   ```bash
   php -l /var/www/html/public/grade/report/transcript/db/upgrade.php
   ```

2. Check Moodle error logs:
   ```bash
   tail -100 /var/moodledata/error.log
   ```

3. Manually check what version upgrade thinks ran:
   ```bash
   # In upgrade.php, find the if ($oldversion < XXXXXXXX) blocks
   # Verify your version number is in one of those blocks
   ```

## What Went Wrong (October 2025 - Incident Log)

### ⚠️ CRITICAL INCIDENT: Data Loss - October 25, 2025
**Severity:** High - Complete data loss for plugin tables

**What Happened:**
- Deployed plugin update v1.0.22 to production
- Database upgrade triggered without taking backups first
- All plugin data deleted (exact cause being investigated)
- No backups existed - data could not be recovered
- Lost all: transfer credits, transcript requests, grading scales, symbols

**Root Causes:**
1. ❌ No backup taken before triggering database upgrade
2. ❌ Maintenance mode not enabled during deployment
3. ❌ No backup verification step in deployment workflow
4. ❌ Possible uninstall/reinstall instead of proper upgrade

**Impact:**
- Production downtime
- Complete loss of plugin data
- Had to restore plugin to previous version
- Users affected (data loss for student records)

**Fixes Implemented:**
1. ✅ Added mandatory 3-part backup checklist to deployment guide
2. ✅ Added maintenance mode requirements section
3. ✅ Added data loss recovery procedures
4. ✅ Added common mistakes section with warnings
5. ✅ Added backup verification step before upgrade trigger
6. ✅ Created automated backup scripts
7. ✅ Created emergency restore script

**Lessons Learned:**
- NEVER deploy plugin updates without backups
- NEVER assume upgrade will work without issues
- ALWAYS verify backups before clicking upgrade
- ALWAYS enable maintenance mode for database changes
- ALWAYS test restore process before needing it

**Prevention:** Follow updated deployment guide religiously. No exceptions.

---

### Mistake 1: Deployed to Two Locations (October 2025)
- ❌ Deployed to `/var/www/html/grade/report/transcript/` (WRONG)
- ✅ Also deployed to `/var/www/html/public/grade/report/transcript/` (CORRECT)
- **Impact:** Confusion about which location is active, wasted disk space
- **Fix:** Removed `/var/www/html/grade/` entirely

### Mistake 2: Didn't Purge Cache (October 2025)
- Files deployed correctly to `/public/grade/report/transcript/`
- But Moodle cache not purged
- Moodle didn't detect version change
- User didn't see upgrade notification
- **Impact:** Appeared broken even though files were correct
- **Fix:** Always run `php admin/cli/purge_caches.php` after deployment

### Mistake 3: Tried to Force Upgrades (October 2025)
- Attempted to run `php admin/cli/upgrade.php`
- **Problem:** This only works for core Moodle, not plugins
- Standard Moodle way: User sees notification → clicks upgrade → automatic
- **Fix:** Trust Moodle's automatic detection, let user trigger upgrade

## Best Practices

### Mandatory (Never Skip These)
1. ✅ **ALWAYS create 3-part backup before deployment** (database, moodledata, code)
2. ✅ **ALWAYS verify backups exist** before user clicks upgrade
3. ✅ **ALWAYS enable maintenance mode** before deploying plugin files
4. ✅ **NEVER uninstall/reinstall plugins** - Use upgrade workflow only
5. ✅ **NEVER make manual database changes** - Use upgrade.php only

### Deployment Workflow
6. **Always** sync both repositories (standalone plugin + main moodle-VM)
7. **Always** increment version number in `version.php` when making database changes
8. **Always** verify files deployed to `/var/www/html/public/` NOT `/var/www/html/`
9. **Always** purge cache after file deployment
10. **Always** let user trigger database upgrade via admin UI (not CLI)

### Verification & Testing
11. **Always** test feature after user completes upgrade
12. **Always** verify database version matches file version
13. **Always** check error logs after upgrade
14. **Always** disable maintenance mode only after verifying success

### Disaster Recovery
15. **Always** download critical backups locally
16. **Always** document what went wrong if upgrade fails
17. **Always** test restore process in staging environment
18. **Always** keep at least 3 recent backups available

## Quick Reference Commands

```bash
# Connect to production VM
gcloud compute ssh moodle-vm-demo --project=sms-edu-47 --zone=us-central1-a

# Purge Moodle cache
php /var/www/html/admin/cli/purge_caches.php

# Check plugin version in files
cat /var/www/html/public/grade/report/transcript/version.php | grep version

# Check plugin version in database
mariadb -u moodle_user -p'PASSWORD' moodle_lms -e "SELECT * FROM mdl_config_plugins WHERE plugin='gradereport_transcript';"

# Fix file permissions
sudo chown -R www-data:www-data /var/www/html/public/grade/report/transcript/

# View Moodle error log
tail -100 /var/moodledata/error.log
```

## Version History

- **v1.0.7 (2025-10-22):** Added program start/graduation/withdrawn dates for official transcripts (AACRAO compliant)
- **v1.0.6 (2025-10-21):** Fixed logo sizing to letterhead standard
- **v1.0.0 (2025-10-18):** Initial plugin creation

## Support

For issues or questions:
- Check this guide first
- Review Moodle error logs: `/var/moodledata/error.log`
- Verify file location: `/var/www/html/public/grade/report/transcript/`
- Check database version matches file version
- Ensure cache was purged after deployment
