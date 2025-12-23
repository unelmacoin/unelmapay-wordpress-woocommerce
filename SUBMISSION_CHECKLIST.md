# WordPress.org Submission Checklist

## ✅ Files Added for WordPress.org

### Required Files
- [x] **readme.txt** - WordPress.org format readme with plugin metadata
- [x] **uninstall.php** - Cleanup on plugin deletion
- [x] **languages/unelmapay-woocommerce.pot** - Translation template
- [x] **.gitignore** - Git ignore file
- [x] **CONTRIBUTING.md** - Contribution guidelines

### Assets Directory (.wordpress-org/)
- [ ] **icon-256x256.png** - Plugin icon (256x256px) - **YOU NEED TO CREATE**
- [ ] **icon-128x128.png** - Plugin icon (128x128px) - **YOU NEED TO CREATE**
- [ ] **banner-772x250.png** - Banner image (772x250px) - **YOU NEED TO CREATE**
- [ ] **banner-1544x500.png** - Banner retina (1544x500px) - **YOU NEED TO CREATE**
- [ ] **screenshot-1.png** - Settings page - **YOU NEED TO CREATE**
- [ ] **screenshot-2.png** - Checkout page - **YOU NEED TO CREATE**
- [ ] **screenshot-3.png** - Payment form - **YOU NEED TO CREATE**
- [ ] **screenshot-4.png** - Order completion - **YOU NEED TO CREATE**

## 📋 Pre-Submission Tasks

### 1. Create Required Images

#### Plugin Icons
Create two versions of your plugin icon:
- **256x256px** PNG with transparent background
- **128x128px** PNG with transparent background
- Should feature UnelmaPay logo or payment gateway icon
- Place in `.wordpress-org/` directory

#### Plugin Banners
Create promotional banners:
- **772x250px** PNG/JPG - Standard banner
- **1544x500px** PNG/JPG - Retina banner
- Should showcase plugin features or UnelmaPay branding
- Place in `.wordpress-org/` directory

#### Screenshots
Take screenshots of:
1. **Settings page** - WooCommerce → Settings → Payments → UnelmaPay
2. **Checkout page** - Customer selecting UnelmaPay payment method
3. **Payment form** - UnelmaPay payment page on dev.unelmapay.com
4. **Order completion** - Successful order with transaction details

Save as PNG files and place in `.wordpress-org/` directory.

### 2. Test the Plugin

- [ ] Install on fresh WordPress site
- [ ] Activate without errors
- [ ] Configure with test credentials
- [ ] Complete test transaction in sandbox mode
- [ ] Verify IPN callback works
- [ ] Check debug logs
- [ ] Test with WP_DEBUG enabled (no errors/warnings)
- [ ] Test deactivation (no errors)
- [ ] Test uninstallation (settings removed)

### 3. Validate readme.txt

Visit: https://wordpress.org/plugins/developers/readme-validator/

Upload your `readme.txt` file and fix any validation errors.

### 4. Code Quality Check

- [ ] No PHP errors with WP_DEBUG enabled
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Follows WordPress coding standards
- [ ] No hardcoded strings (all translatable)
- [ ] Proper text domain usage
- [ ] No external dependencies (or documented)

### 5. Security Review

- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] Nonce verification on forms
- [ ] Capability checks for admin functions
- [ ] Secure IPN handling
- [ ] No sensitive data in frontend

## 📦 Create Plugin ZIP

Once all files are ready:

```bash
cd /Users/sk/CascadeProjects/windsurf-project-2/
zip -r unelmapay-woocommerce.zip unelmapay-woocommerce/ \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*.DS_Store" \
  -x "*README.md" \
  -x "*CHANGES.md" \
  -x "*TESTING.md" \
  -x "*WORDPRESS_ORG_SUBMISSION.md" \
  -x "*SUBMISSION_CHECKLIST.md"
```

## 🚀 Submit to WordPress.org

### Step 1: Create WordPress.org Account
If you don't have one: https://login.wordpress.org/register

### Step 2: Submit Plugin
1. Go to: https://wordpress.org/plugins/developers/add/
2. Log in with your WordPress.org account
3. Upload the plugin ZIP file
4. Fill in the submission form
5. Submit for review

### Step 3: Wait for Review
- Review typically takes 2-14 days
- You'll receive email updates
- Plugin Review Team may request changes

### Step 4: After Approval
You'll receive SVN repository access:

```bash
# Checkout your SVN repository
svn co https://plugins.svn.wordpress.org/unelmapay-woocommerce

cd unelmapay-woocommerce

# Copy plugin files to trunk
cp -r /path/to/unelmapay-woocommerce/* trunk/

# Copy assets separately
cp -r /path/to/unelmapay-woocommerce/.wordpress-org/* assets/

# Add to SVN
svn add trunk/*
svn add assets/*

# Commit
svn ci -m "Initial commit v2.0.0"

# Tag the release
svn cp trunk tags/2.0.0
svn ci -m "Tagging version 2.0.0"
```

## 📝 Post-Submission

### Monitor Plugin
- Watch for user reviews
- Monitor support forum
- Track download stats
- Respond to support requests

### Future Updates
When releasing updates:

1. Update version in plugin header
2. Update version in readme.txt
3. Add changelog entry
4. Test thoroughly
5. Commit to SVN trunk
6. Tag new version in SVN

```bash
# Update trunk
svn up
# Make changes
svn ci -m "Update to version 2.1.0"

# Tag new version
svn cp trunk tags/2.1.0
svn ci -m "Tagging version 2.1.0"
```

## 🎯 Current Status

### ✅ Completed
- Plugin code written and tested
- WooCommerce integration complete
- Sandbox and production modes working
- IPN callback implemented
- Debug logging added
- readme.txt created
- Translation template created
- Uninstall script added
- Documentation complete

### ⚠️ TODO (Before Submission)
- Create plugin icon images (256x256, 128x128)
- Create banner images (772x250, 1544x500)
- Take screenshots (4 images)
- Validate readme.txt
- Final testing on clean WordPress install
- Create submission ZIP file

## 📚 Resources

- [Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [readme.txt Validator](https://wordpress.org/plugins/developers/readme-validator/)
- [SVN Guide](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)

## 💡 Tips

1. **Be Patient**: Review process can take time
2. **Be Responsive**: Reply quickly to review team requests
3. **Follow Guidelines**: Read and follow all WordPress.org guidelines
4. **Test Thoroughly**: Test on multiple environments
5. **Document Well**: Clear documentation helps users and reviewers
6. **Support Users**: Active support builds trust and ratings

## 📧 Contact

- WordPress.org Plugin Review: plugins@wordpress.org
- UnelmaPay Support: support@unelmapay.com
