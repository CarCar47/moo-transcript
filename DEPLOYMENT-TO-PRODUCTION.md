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

## Complete Deployment Workflow

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

### Step 5: Deploy to Production VM
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

**THE USER DOES THIS - NOT YOU:**

1. User logs into https://lms.cor4edu.us as admin
2. **Moodle automatically detects** version change from `version.php`
3. **Moodle forces upgrade notification** - user cannot bypass this
4. User clicks "Upgrade Moodle database now"
5. Moodle runs `db/upgrade.php` automatically
6. Database columns/tables created
7. Success message displayed

**NEVER:**
- Force database upgrades via CLI (`php admin/cli/upgrade.php`)
- Run SQL migrations manually
- Skip or override the upgrade process
- Assume upgrade happened without user confirmation

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

## What Went Wrong (October 2025)

### Mistake 1: Deployed to Two Locations
- ❌ Deployed to `/var/www/html/grade/report/transcript/` (WRONG)
- ✅ Also deployed to `/var/www/html/public/grade/report/transcript/` (CORRECT)
- **Impact:** Confusion about which location is active, wasted disk space
- **Fix:** Removed `/var/www/html/grade/` entirely

### Mistake 2: Didn't Purge Cache
- Files deployed correctly to `/public/grade/report/transcript/`
- But Moodle cache not purged
- Moodle didn't detect version change
- User didn't see upgrade notification
- **Impact:** Appeared broken even though files were correct
- **Fix:** Always run `php admin/cli/purge_caches.php` after deployment

### Mistake 3: Tried to Force Upgrades
- Attempted to run `php admin/cli/upgrade.php`
- **Problem:** This only works for core Moodle, not plugins
- Standard Moodle way: User sees notification → clicks upgrade → automatic
- **Fix:** Trust Moodle's automatic detection, let user trigger upgrade

## Best Practices

1. **Always** sync both repositories (standalone plugin + main moodle-VM)
2. **Always** purge cache after file deployment
3. **Never** force database upgrades
4. **Always** increment version number in `version.php` when making changes
5. **Always** verify files deployed to `/var/www/html/public/` NOT `/var/www/html/`
6. **Always** let user trigger database upgrade via admin UI
7. **Always** test feature after user completes upgrade

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
