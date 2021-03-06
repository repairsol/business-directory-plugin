<?php

class WPBDP_Settings {

    const PREFIX = 'wpbdp-';

    const _EMAIL_RENEWAL_MESSAGE = "Your listing \"[listing]\" in category [category] expired on [expiration]. To renew your listing click the link below.\n[link]";
    const _EMAIL_PENDING_RENEWAL_MESSAGE = 'Your listing "[listing]" is about to expire at [site]. You can renew it here: [link].';

    public function __construct() {
        $this->groups = array();
        $this->settings = array();

        add_action( 'plugins_loaded', array( &$this, 'register_settings' ), 20 );
    }

    public function register_settings() {
        /* General settings */
        $g = $this->add_group('general', _x('General', 'admin settings', 'WPBDM'));

        $s = $this->add_section( $g, 'tracking', _x( 'Data Collection', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'tracking-on',
                            _x( 'Allow BD to anonymously collect information about your installed plugins, themes and WP version?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            str_replace( '<a>',
                                         '<a href="http://businessdirectoryplugin.com/what-we-track/" target="_blank">',
                                         _x( '<a>Learn more</a> about what BD does and does NOT track.', 'admin settings', 'WPBDM' ) )
                          );

        $s = $this->add_section($g, 'permalink', _x('Permalink Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'permalinks-directory-slug', _x('Directory Listings Slug', 'admin settings', 'WPBDM'), 'text', WPBDP_POST_TYPE, null, null, array($this, '_validate_listings_permalink'));
        $this->add_setting($s, 'permalinks-category-slug', _x('Categories Slug', 'admin settings', 'WPBDM'), 'text', WPBDP_CATEGORY_TAX, _x('The slug can\'t be in use by another term. Avoid "category", for instance.', 'admin settings', 'WPBDM'), null, array($this, '_validate_term_permalink'));
        $this->add_setting($s, 'permalinks-tags-slug', _x('Tags Slug', 'admin settings', 'WPBDM'), 'text', WPBDP_TAGS_TAX, _x('The slug can\'t be in use by another term. Avoid "tag", for instance.', 'admin settings', 'WPBDM'), null, array($this, '_validate_term_permalink'));

        $s = $this->add_section( $g,
                                 'recaptcha',
                                 _x( 'reCAPTCHA Settings', 'admin settings', 'WPBDM' ),
                                 str_replace( '<a>',
                                              '<a href="http://www.recaptcha.com" target="_blank">',
                                              _x( 'Need API keys for reCAPTCHA? Get them <a>here</a>.', 'admin settings', 'WPBDM' ) )
                                );
        $this->add_setting($s, 'recaptcha-on', _x('Use reCAPTCHA for contact forms', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'recaptcha-for-submits', _x('Use reCAPTCHA for listing submits', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting( $s,
                            'recaptcha-for-comments',
                            _x( 'Use reCAPTCHA for listing comments?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false );
        $this->add_setting($s, 'recaptcha-public-key', _x('reCAPTCHA Public Key', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'recaptcha-private-key', _x('reCAPTCHA Private Key', 'admin settings', 'WPBDM'));

        $s = $this->add_section( $g,
                                 'terms-and-conditions',
                                 _x( 'Terms and Conditions', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'display-terms-and-conditions',
                            _x( 'Display and require user agreement to Terms and Conditions', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false
                          );
        $this->add_setting( $s,
                            'terms-and-conditions',
                            _x( 'Terms and Conditions', 'admin settings', 'WPBDM' ),
                            'text',
                            "Terms and Conditions text goes here...\n\n",
                            _x( 'Enter text or a URL starting with http. If you use a URL, the Terms and Conditions text will be replaced by a link to the appropiate page.', 'admin settings', 'WPBDM' ),
                            array( 'use_textarea' => true )
                            );

        $s = $this->add_section($g, 'displayoptions', _x('Directory Display Options', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'show-submit-listing', _x('Show the "Submit listing" button.', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-search-listings', _x('Show "Search listings".', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-view-listings', _x('Show the "View Listings" button.', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-directory-button', _x('Show the "Directory" button.', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-search-form-in-results', _x('Display search form when displaying search results?', 'admin settings', 'WPBDM'), 'boolean', true);

        $s = $this->add_section($g, 'misc', _x('Miscellaneous Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'hide-tips', _x('Hide tips for use and other information?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'credit-author', _x('Give credit to plugin author?', 'admin settings', 'WPBDM'), 'boolean', true);

        /* Listings settings */
        $g = $this->add_group('listings', _x('Listings', 'admin settings', 'WPBDM'));
        $s = $this->add_section($g, 'general', _x('General Settings', 'admin settings', 'WPBDM'));
        
        $this->add_setting($s, 'listings-per-page', _x('Listings per page', 'admin settings', 'WPBDM'), 'text', '10',
                           _x('Number of listings to show per page. Use a value of "0" to show all listings.', 'admin settings', 'WPBDM'));

        $this->add_setting($s, 'listing-duration', _x('Listing duration for no-fee sites (in days)', 'admin settings', 'WPBDM'), 'text', '365',
                           _x('Use a value of "0" to keep a listing alive indefinitely or enter a number less than 10 years (3650 days).', 'admin settings', 'WPBDM'),
                           null,
                           array($this, '_validate_listing_duration'));
        
        $this->add_setting( $s,
                            'show-contact-form', 
                            _x( 'Include listing contact form on listing pages?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            true,
                            _x( 'Allows visitors to contact listing authors privately. Authors will receive the messages via email.', 'admin settings', 'WPBDM' ) );
        $this->add_setting( $s,
                            'show-comment-form',
                            _x( 'Include comment form on listing pages?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x( 'Allow visitors to discuss listings using the standard WordPress comment form. Comments are public.', 'admin settings', 'WPBDM' ) );
        $this->add_setting($s, 'show-listings-under-categories', _x('Show listings under categories on main page?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting( $s,
                            'override-email-blocking',
                            _x( 'Override email Blocking?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false,
                            _x('When checked, email addresses in listing fields will be displayed publicly. Not recommended.', 'admin settings', 'WPBDM') );
        $this->add_setting($s, 'status-on-uninstall', _x('Status of listings upon uninstalling plugin', 'admin settings', 'WPBDM'), 'choice', 'trash', '',
                           array('choices' => array('draft', 'trash')));
        $this->add_setting($s, 'deleted-status', _x('Status of deleted listings', 'admin settings', 'WPBDM'), 'choice', 'trash', '',
                           array('choices' => array('draft', 'trash')));

        $s = $this->add_section($g, 'listings/email', _x('Listing email settings', 'admin settings', 'WPBDM'));
        $this->add_setting( $s,
                            'listing-email-mode',
                            _x( 'How to determine the listing\'s email address?', 'admin settings', 'WPBDM' ),
                            'choice',
                            'field',
                            _x( 'This affects emails sent to listing owners via contact forms or when their listings expire.', 'admin settings', 'WPBDM' ),
                            array( 'choices' => array(
                                array( 'field', 'Try listing\'s email field first, then author\'s email.' ),
                                array( 'user',  'Try author\'s email first and then listing\'s email field.' )

                            ) ) );

        $this->add_setting( $s, 'notify-admin', _x( 'Notify admin of new listings via email?', 'admin settings', 'WPBDM' ), 'boolean', false );
        $this->add_setting($s, 'send-email-confirmation', _x('Send email confirmation to listing owner when listing is submitted?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'email-confirmation-message', _x('Email confirmation message', 'admin settings', 'WPBDM'), 'text',
                           'Your submission \'[listing]\' has been received and it\'s pending review. This review process could take up to 48 hours.',
                          _x('You can use the placeholder [listing] for the listing title. This setting applies to non-paying listings only; for paying listings check the "Payment" settings tab.', 'admin settings', 'WPBDM'));

        $s = $this->add_section($g, 'listings/renewals', _x('Listing Renewal', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'listing-renewal', _x('Turn on listing renewal option?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting( $s,
                            'renewal-email-threshold',
                            _x( 'Listing renewal e-mail threshold (in days)', 'admin settings', 'WPBDM' ),
                            'text',
                            '5',
                            _x( 'Configure how many days before listing expiration is the renewal e-mail sent.', 'admin settings', 'WPBDM' )
                            );
        $this->add_setting( $s,
                            'renewal-pending-message',
                            _x( 'Pending expiration e-mail message', 'admin settings', 'WPBDM' ),
                            'text',
                            self::_EMAIL_PENDING_RENEWAL_MESSAGE,
                            '',
                            array( 'use_textarea' => true ));
        $this->add_setting( $s,
                            'listing-renewal-message', _x('Listing Renewal e-mail message', 'admin settings', 'WPBDM'),
                            'text',
                            self::_EMAIL_RENEWAL_MESSAGE,
                            _x( 'You can use the placeholders [listing] for the listing title, [category] for the category, [expiration] for the expiration date and [link] for the actual renewal link.', 'admin settings', 'WPBDM' ),
                            array( 'use_textarea' => true )
                          );

        // Renewal Reminders
        $this->add_setting( $s,
                            'renewal-reminder',
                            _x( 'Remind listing owners of expired listings (past due)?', 'admin settings', 'WPBDM' ),
                            'boolean',
                            false );
        $this->add_setting( $s,
                            'renewal-reminder-threshold',
                            _x( 'Listing renewal reminder e-mail threshold (in days)', 'admin settings', 'WPBDM' ),
                            'text',
                            '10',
                            _x( 'Configure how many days after the expiration of a listing an e-mail reminder should be sent to the owner.', 'admin settings', 'WPBDM' )
                          );
        $this->add_setting( $s,
                            'renewal-reminder-message',
                            _x( 'Renewal reminder e-mail message', 'admin settings', 'WPBDM' ),
                            'text',
                            "Dear Customer\nWe've noticed that you haven't renewed your listing \"[listing]\" for category [category] at [site] and just wanted to remind you that it expired on [expiration]. Please remember you can still renew it here: [link].",
                            _x( 'You can use the placeholders [listing] for the listing title, [category] for the category, [expiration] for the expiration date, [site] for this site\'s URL and [link] for the actual renewal link.', 'admin settings', 'WPBDM' ),
                            array( 'use_textarea' => true )
                          );

        $s = $this->add_section($g, 'post/category', _x('Post/Category Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'new-post-status', _x('Default new post status', 'admin settings', 'WPBDM'), 'choice', 'pending', '',
                           array('choices' => array('publish', 'pending'))
                           );
        $this->add_setting($s, 'edit-post-status', _x('Edit post status', 'admin settings', 'WPBDM'), 'choice', 'publish', '',
                           array('choices' => array('publish', 'pending')));
        $this->add_setting( $s, 'categories-order-by', _x('Order categories list by', 'admin settings', 'WPBDM'), 'choice', 'name', '',
                           array('choices' => array(
                            array( 'name', _x( 'Name', 'admin settings', 'WPBDM' ) ),
                            array( 'slug', _x( 'Slug', 'admin settings', 'WPBDM' ) ),
                            array( 'count', _x( 'Listing Count', 'admin settings', 'WPBDM' ) )
                           )) );
        $this->add_setting( $s, 'categories-sort', _x('Sort order for categories', 'admin settings', 'WPBDM'), 'choice', 'ASC', '',
                           array('choices' => array(array('ASC', _x('Ascending', 'admin settings', 'WPBDM')), array('DESC', _x('Descending', 'admin settings', 'WPBDM')))));
        $this->add_setting($s, 'show-category-post-count', _x('Show category post count?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'hide-empty-categories', _x('Hide empty categories?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-only-parent-categories', _x('Show only parent categories in category list?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'listings-order-by', _x('Order directory listings by', 'admin settings', 'WPBDM'), 'choice', 'title', '',
                          array('choices' => array(
                            array( 'title', _x( 'Title', 'admin settings', 'WPBDM' ) ),
                            array( 'author', _x( 'Author', 'admin settings', 'WPBDM' ) ),
                            array( 'date', _x( 'Date posted', 'admin settings', 'WPBDM' ) ),
                            array( 'modified', _x( 'Date last modified', 'admin settings', 'WPBDM' ) ),
                            array( 'rand', _x( 'Random', 'admin settings', 'WPBDM' ) ),
                            array( 'paid', _x( 'Paid first then free', 'admin settings', 'WPBDM' ) )
                          )));
        $this->add_setting( $s, 'listings-sort', _x('Sort directory listings by', 'admin settings', 'WPBDM'), 'choice', 'ASC',
                           _x('Ascending for ascending order A-Z, Descending for descending order Z-A', 'admin settings', 'WPBDM'),
                           array('choices' => array(array('ASC', _x('Ascending', 'admin settings', 'WPBDM')), array('DESC', _x('Descending', 'admin settings', 'WPBDM')))));

        $s = $this->add_section($g, 'featured', _x('Featured (Sticky) listing settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'featured-on', _x('Offer sticky listings?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'featured-offer-in-submit', _x('Offer upgrades during submit process?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'featured-price', _x('Sticky listing price', 'admin settings', 'WPBDM'), 'text', '39.99');
        $this->add_setting($s, 'featured-description', _x('Sticky listing page description text', 'admin settings', 'WPBDM'), 'text',
                           _x('You can upgrade your listing to featured status. Featured listings will always appear on top of regular listings.', 'admin settings', 'WPBDM'));

        /* Payment settings */
        $g = $this->add_group('payment', _x('Payment', 'admin settings', 'WPBDM'));
        $s = $this->add_section($g, 'general', _x('Payment Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'payments-on', _x('Turn On payments?', 'admin settings', 'WPBDM'), 'boolean', false);
        $this->add_setting($s, 'payments-test-mode', _x('Put payment gateways in test mode?', 'admin settings', 'WPBDM'), 'boolean', true);

        // PayPal currency codes from https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_currency_codes
        $this->add_setting($s, 'currency', _x('Currency Code', 'admin settings', 'WPBDM'), 'choice', 'USD', '',
                            array('choices' => array(
                                array('AUD', _x('Australian Dollar (AUD)', 'admin settings', 'WPBDM')),
                                array('BRL', _x('Brazilian Real (BRL)', 'admin settings', 'WPBDM')),
                                array('CAD', _x('Canadian Dollar (CAD)', 'admin settings', 'WPBDM')),
                                array('CZK', _x('Czech Koruna (CZK)', 'admin settings', 'WPBDM')),
                                array('DKK', _x('Danish Krone (DKK)', 'admin settings', 'WPBDM')),
                                array('EUR', _x('Euro (EUR)', 'admin settings', 'WPBDM')),
                                array('HKD', _x('Hong Kong Dollar (HKD)', 'admin settings', 'WPBDM')),
                                array('HUF', _x('Hungarian Forint (HUF)', 'admin settings', 'WPBDM')),
                                array('ILS', _x('Israeli New Shequel (ILS)', 'admin settings', 'WPBDM')),
                                array('JPY', _x('Japanese Yen (JPY)', 'admin settings', 'WPBDM')),
                                array('MYR', _x('Malasian Ringgit (MYR)', 'admin settings', 'WPBDM')),
                                array('MXN', _x('Mexican Peso (MXN)', 'admin settings', 'WPBDM')),
                                array('NOK', _x('Norwegian Krone (NOK)', 'admin settings', 'WPBDM')),
                                array('NZD', _x('New Zealand Dollar (NZD)', 'admin settings', 'WPBDM')),
                                array('PHP', _x('Philippine Peso (PHP)', 'admin settings', 'WPBDM')),
                                array('PLN', _x('Polish Zloty (PLN)', 'admin settings', 'WPBDM')),
                                array('GBP', _x('Pound Sterling (GBP)', 'admin settings', 'WPBDM')),
                                array('SGD', _x('Singapore Dollar (SGD)', 'admin settings', 'WPBDM')),
                                array('SEK', _x('Swedish Krona (SEK)', 'admin settings', 'WPBDM')),
                                array('CHF', _x('Swiss Franc (CHF)', 'admin settings', 'WPBDM')),
                                array('TWD', _x('Taiwan Dollar (TWD)', 'admin settings', 'WPBDM')),
                                array('THB', _x('Thai Baht (THB)', 'admin settings', 'WPBDM')),
                                array('TRY', _x('Turkish Lira (TRY)', 'admin settings', 'WPBDM')),
                                array('USD', _x('U.S. Dollar', 'admin settings', 'WPBDM')),
                            )));
        $this->add_setting($s, 'currency-symbol', _x('Currency Symbol', 'admin settings', 'WPBDM'), 'text', '$');
        $this->add_setting($s, 'payment-message', _x('Thank you for payment message', 'admin settings', 'WPBDM'), 'text',
                        _x('Thank you for your payment. Your payment is being verified and your listing reviewed. The verification and review process could take up to 48 hours.', 'admin settings', 'WPBDM'));

        // $s = $this->add_section($g, 'googlecheckout', _x('Google Checkout Settings', 'admin settings', 'WPBDM'));
        // $this->add_setting($s, 'googlecheckout', _x('Activate Google Checkout?', 'admin settings', 'WPBDM'), 'boolean', false);
        // $this->add_setting($s, 'googlecheckout-merchant', _x('Google Checkout Merchant ID', 'admin settings', 'WPBDM'));
        // // $this->add_setting($s, 'googlecheckout-seller', _x('Google Checkout Seller ID', 'admin settings', 'WPBDM'));

        $s = $this->add_section($g, 'paypal', _x('PayPal Gateway Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'paypal', _x('Activate Paypal?', 'admin settings', 'WPBDM'), 'boolean', false,
                           _x('Will only work when the <a href="http://businessdirectoryplugin.com/premium-modules/">PayPal module</a> is installed', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'paypal-business-email', _x('PayPal Business Email', 'admin settings', 'WPBDM'));

        $s = $this->add_section($g, '2checkout', _x('2Checkout Gateway Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, '2checkout', _x('Activate 2Checkout?', 'admin settings', 'WPBDM'), 'boolean', false,
                           _x('Will only work when the <a href="http://businessdirectoryplugin.com/premium-modules/">2Checkout module</a> is installed', 'admin settings', 'WPBDM'));
        $this->add_setting($s, '2checkout-seller', _x('2Checkout seller/vendor ID', 'admin settings', 'WPBDM'));

        /* Registration settings */
        $g = $this->add_group('registration', _x('Registration', 'admin settings', 'WPBDM'));
        $s = $this->add_section($g, 'registration', _x('Registration Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'require-login', _x('Require login?', 'admin settings', 'WPBDM'), 'boolean', true);
        //$this->add_setting($s, 'login-url', _x('Login URL', 'admin settings', 'WPBDM'), 'text', wp_login_url()); // deprecated as of 2.1
        //$this->add_setting($s, 'registration-url', _x('Registration URL', 'admin settings', 'WPBDM'), 'text', wp_login_url()); // deprecated as of 2.1

        /* Image settings */
        $g = $this->add_group('image', _x('Image', 'admin settings', 'WPBDM'));
        $s = $this->add_section($g, 'image', _x('Image Settings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'allow-images', _x('Allow images?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'image-max-filesize', _x('Max Image File Size (KB)', 'admin settings', 'WPBDM'), 'text', '10000');
        // $this->add_setting($s, 'image-min-filesize', _x('Minimum Image File Size (KB)', 'admin settings', 'WPBDM'), 'text', '50');
        $this->add_setting($s, 'image-max-width', _x('Max image width', 'admin settings', 'WPBDM'), 'text', '500');
        $this->add_setting($s, 'image-max-height', _x('Max image height', 'admin settings', 'WPBDM'), 'text', '500');
        $this->add_setting($s, 'thumbnail-width', _x('Thumbnail width', 'admin settings', 'WPBDM'), 'text', '150');
        $this->add_setting( $s, 'use-thickbox', _x( 'Turn on thickbox/lightbox?', 'admin settings', 'WPBDM' ), 'boolean', false, _x( 'Uncheck if it conflicts with other elements or plugins installed on your site', 'admin settings', 'WPBDM' ) );

        $s = $this->add_section($g, 'listings', _x('Listings', 'admin settings', 'WPBDM'));
        $this->add_setting($s, 'free-images', _x('Number of free images', 'admin settings', 'WPBDM'), 'text', '2');
        $this->add_setting($s, 'use-default-picture', _x('Use default picture for listings with no picture?', 'admin settings', 'WPBDM'), 'boolean', true);
        $this->add_setting($s, 'show-thumbnail', _x('Show Thumbnail on main listings page?', 'admin settings', 'WPBDM'), 'boolean', true);
    }

    public function _validate_listings_permalink($setting, $newvalue, $oldvalue=null) {
        return trim(str_replace(' ', '', $newvalue));
    }

    public function _validate_term_permalink($setting, $newvalue, $oldvalue=null) {
        $bd_taxonomy = $setting->name == 'permalinks-category-slug' ? WPBDP_CATEGORY_TAX : WPBDP_TAGS_TAX;
        foreach (get_taxonomies(null, 'objects') as $taxonomy) {
            if ($taxonomy->rewrite && $taxonomy->rewrite['slug'] == $newvalue && $taxonomy->name != $bd_taxonomy) {
                return $oldvalue;
            }
        }

        return trim(str_replace(' ', '', $newvalue));
    }

    public function _validate_listing_duration($setting, $newvalue, $oldvalue=null) {
        // limit 'duration' because of TIMESTAMP limited range (issue #157).
        // FIXME: this is not a long-term fix. we should move to DATETIME to avoid this entirely.
        $v = min(max(intval($newvalue), 0), 3650);
        return $v;
    }

    public function add_group($slug, $name, $help_text='') {
        $group = new StdClass();
        $group->wpslug = self::PREFIX . $slug;
        $group->slug = $slug;
        $group->name = esc_attr( $name );
        $group->help_text = $help_text;
        $group->sections = array();

        $this->groups[$slug] = $group;

        return $slug;
    }

    public function add_section($group_slug, $slug, $name, $help_text='') {
        $section = new StdClass();
        $section->name = esc_attr( $name );
        $section->slug = $slug;
        $section->help_text = $help_text;
        $section->settings = array();

        $this->groups[$group_slug]->sections[$slug] = $section;

        return "$group_slug:$slug";
    }

    public function add_core_setting( $name, $default=null ) {
        $setting = new StdClass();
        $setting->name = $name;
        $setting->label = '';
        $setting->help_text = '';
        $setting->default = $default;
        $setting->type = 'core';
        $setting->args = array();
        $setting->validator = '';

        if ( !isset( $this->settings[ $name ] ) ) {
            $this->settings[ $name ] = $setting;
        }

        return true;
    }

    public function add_setting($section_key, $name, $label, $type='text', $default=null, $help_text='', $args=array(), $validator=null, $callback=null) {
        if ( $type == 'core' )
            return $this->add_core_setting( $name, $default );

        list($group, $section) = explode(':', $section_key);
        $args = !$args ? array() : $args;

        if (!$group || !$section)
            return false;

        if ( isset($this->groups[$group]) && isset($this->groups[$group]->sections[$section]) ) {
            $_default = $default;
            if (is_null($_default)) {
                switch ($type) {
                    case 'text':
                    case 'choice':
                        $_default = '';
                        break;
                    case 'boolean':
                        $_default = false;
                        break;
                    default:
                        $_default = null;
                        break;
                }
            }

            $setting = new StdClass();
            $setting->name = esc_attr( $name );
            $setting->label = $label;
            $setting->help_text = $help_text;
            $setting->default = $_default;
            $setting->type = $type;
            $setting->args = $args;
            $setting->validator = $validator;
            $setting->callback = $callback;

            $this->groups[$group]->sections[$section]->settings[$name] = $setting;
        }

        if (!isset($this->settings[$name])) {
            $this->settings[$name] = $setting;
        }

        return true;
    }

    public function get($name, $ifempty=null) {
        $value =  get_option(self::PREFIX . $name, null);

        if (is_null($value)) {
            $default_value = isset($this->settings[$name]) ? $this->settings[$name]->default : null;

            if (is_null($default_value))
                return $ifempty;

            return $default_value;
        }

        if (!is_null($ifempty) && empty($value))
            $value = $ifempty;

        if ($this->settings[$name]->type == 'boolean')
            return (boolean) intval($value);

        return $value;
    }

    public function set($name, $value, $onlyknown=true) {
        $name = strtolower($name);

        if ($onlyknown && !isset($this->settings[$name]))
            return false;

        if (isset($this->settings[$name]) && $this->settings[$name]->type == 'boolean') {
            $value = (boolean) intval($value);
        }

        // wpbdp_debug("Setting $name = $value");
        update_option(self::PREFIX . $name, $value);

        return true;
    }

    /* emulates get_wpbusdirman_config_options() in version 2.0 until
     * all deprecated code has been ported. */
    public function pre_2_0_compat_get_config_options() {
        $legacy_options = array();

        foreach ($this->pre_2_0_options() as $old_key => $new_key) {
            $setting_value = $this->get($new_key);

            if ($new_key == 'googlecheckout' || $new_key == 'paypal' || $new_key == '2checkout')
                $setting_value = !$setting_value;

            if ($this->settings[$new_key]->type == 'boolean') {
                $setting_value = $setting_value == true ? 'yes' : 'no';
            }

            $legacy_options[$old_key] = $setting_value;
        }

        return $legacy_options;
    }



    public function reset_defaults() {
        foreach ($this->settings as $setting) {
            delete_option(self::PREFIX . $setting->name);
        }
    }

    /*
     * admin
     */
    public function _setting_custom($args) {
        $setting = $args['setting'];
        $value = $this->get( $setting->name );

        $html = '';

        ob_start();
        call_user_func( $setting->callback, $setting, $value );
        $custom_content = ob_get_contents();
        ob_end_clean();

        $html .= $custom_content;

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function _setting_text($args) {
        $setting = $args['setting'];
        $value = $this->get($setting->name);

        if (isset($args['use_textarea']) || strlen($value) > 100) {
            $html  = '<textarea id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '" cols="80" rows="4">';
            $html .= esc_textarea($value);
            $html .= '</textarea><br />';
        } else {
            $html = '<input type="text" id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '" value="' . esc_attr( $value ) . '" size="' . (strlen($value) > 0 ? strlen($value) : 20). '" />';
        }

        $html .= '<span class="description">' . $setting->help_text . '</span>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function _setting_boolean($args) {
        $setting = $args['setting'];

        $value = (boolean) $this->get($setting->name);

        $html  = '<label for="' . $setting->name . '">';
        $html .= '<input type="checkbox" id="' .$setting->name . '" name="' . self::PREFIX . $setting->name . '" value="1" '
                  . ($value ? 'checked="checked"' : '') . '/>';
        $html .= '&nbsp;<span class="description">' . $setting->help_text . '</span>';
        $html .= '</label>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function _setting_choice($args) {
        $setting = $args['setting'];
        $choices = $args['choices'];

        $value = $this->get($setting->name);

        $multiple = isset( $args['multiple'] ) && $args['multiple'] ? true : false;
        $widget = $multiple ? ( isset( $args['use_checkboxes'] ) && $args['use_checkboxes'] ? 'checkbox' : 'multiselect' ) : 'select'; // TODO: Add support for radios.
        $html = '';

        if ( $widget == 'select' || $widget == 'multiselect' ) {
            // TODO: Add support for multiple.
            $html .= '<select id="' . $setting->name . '" name="' . self::PREFIX . $setting->name . '">';
            
            foreach ($choices as $ch) {
                $opt_label = is_array($ch) ? $ch[1] : $ch;
                $opt_value = is_array($ch) ? $ch[0] : $ch;

                $html .= '<option value="' . $opt_value . '"' . ($value == $opt_value ? ' selected="selected"' : '') . '>'
                                . $opt_label . '</option>';
            }

            $html .= '</select>';
        } elseif ( $widget == 'checkbox' ) {
            foreach ( $choices as $k => $v ) {
                $html .= sprintf( '<label><input type="checkbox" name="%s[]" value="%s" %s />%s</label> ',
                                  $setting->name,
                                  $k,
                                  in_array( $k, $value, true ) ? 'checked="checked"' : '',
                                  $v );
            }
        }

        $html .= '<span class="description">' . $setting->help_text . '</span>';

        echo apply_filters( 'wpbdp_settings_render', $html, $setting, $args );
    }

    public function register_in_admin() {
        foreach ($this->groups as $group) {
            foreach ($group->sections as $section) {
                $callback = create_function('', ';');

                if ($section->help_text)
                    $callback = create_function('', 'echo "<p class=\"description\">' . addslashes( $section->help_text ) . '</p>";');

                add_settings_section($section->slug, $section->name, $callback, $group->wpslug);

                foreach ($section->settings as $setting) {
                    register_setting($group->wpslug, self::PREFIX . $setting->name);
                    add_settings_field(self::PREFIX . $setting->name, $setting->label,
                                       array($this, '_setting_' . $setting->type),
                                       $group->wpslug,
                                       $section->slug,
                                       array_merge($setting->args, array('label_for' => $setting->name, 'setting' => $setting))
                                       );

                    if ( $setting->validator || ( $setting->type == 'choice' && isset( $setting->args['multiple'] ) && $setting->args['multiple'] ) ) {
                        add_filter('pre_update_option_' . self::PREFIX . $setting->name, create_function('$n, $o=null', 'return WPBDP_Settings::_validate_setting("' . $setting->name . '", $n, $o);'), 2);
                    }
                }
            }
        }
    }

    public static function _validate_setting($name, $newvalue=null, $oldvalue=null) {
        $api = wpbdp_settings_api();
        $setting = $api->settings[$name];

        if ( $setting->type == 'choice' && isset( $setting->args['multiple'] ) && $setting->args['multiple'] ) {
            if ( isset( $_POST[ $name ] ) ) {
                $newvalue = $_POST[ $name ];
                $newvalue = is_array( $newvalue ) ? $newvalue : array( $newvalue );

                if ( $setting->validator )
                    $newvalue = call_user_func( $setting->validator, $setting, $newvalue, $api->get( $setting->name ) );

                return $newvalue;
            }
        }

        return call_user_func($setting->validator, $setting, $newvalue, $api->get($setting->name));
    }

    /* upgrade from old-style settings to new options */
    public function pre_2_0_options() {
        static $option_translations = array(
            'wpbusdirman_settings_config_18' => 'listing-duration',
            /* 'wpbusdirman_settings_config_25' => 'hide-buy-module-buttons',*/  /* removed in 2.0 */
            'wpbusdirman_settings_config_26' => 'hide-tips',
            'wpbusdirman_settings_config_27' => 'show-contact-form',
            'wpbusdirman_settings_config_36' => 'show-comment-form',
            'wpbusdirman_settings_config_34' => 'credit-author',
            'wpbusdirman_settings_config_38' => 'listing-renewal',
            'wpbusdirman_settings_config_39' => 'use-default-picture',
            'wpbusdirman_settings_config_44' => 'show-listings-under-categories',
            'wpbusdirman_settings_config_45' => 'override-email-blocking',
            'wpbusdirman_settings_config_46' => 'status-on-uninstall',
            'wpbusdirman_settings_config_47' => 'deleted-status',
            'wpbusdirman_settings_config_3' => 'require-login',
            'wpbusdirman_settings_config_4' => 'login-url',
            'wpbusdirman_settings_config_5' => 'registration-url',
            'wpbusdirman_settings_config_1' => 'new-post-status',
            'wpbusdirman_settings_config_19' => 'edit-post-status',
            'wpbusdirman_settings_config_7' => 'categories-order-by',
            'wpbusdirman_settings_config_8' => 'categories-sort',
            'wpbusdirman_settings_config_9' => 'show-category-post-count',
            'wpbusdirman_settings_config_10' => 'hide-empty-categories',
            'wpbusdirman_settings_config_48' => 'show-only-parent-categories',
            'wpbusdirman_settings_config_52' => 'listings-order-by',
            'wpbusdirman_settings_config_53' => 'listings-sort',
            'wpbusdirman_settings_config_6' => 'allow-images',
            'wpbusdirman_settings_config_2' => 'free-images',
            'wpbusdirman_settings_config_11' => 'show-thumbnail',
            'wpbusdirman_settings_config_13' => 'image-max-filesize',
            'wpbusdirman_settings_config_14' => 'image-min-filesize',
            'wpbusdirman_settings_config_15' => 'image-max-width',
            'wpbusdirman_settings_config_16' => 'image-max-height',
            'wpbusdirman_settings_config_17' => 'thumbnail-width',
            'wpbusdirman_settings_config_20' => 'currency',
            'wpbusdirman_settings_config_12' => 'currency-symbol',
            'wpbusdirman_settings_config_21' => 'payments-on',
            'wpbusdirman_settings_config_22' => 'payments-test-mode',
            'wpbusdirman_settings_config_37' => 'payment-message',
            'wpbusdirman_settings_config_23' => 'googlecheckout-merchant',
            'wpbusdirman_settings_config_24' => 'googlecheckout-seller',
            'wpbusdirman_settings_config_40' => 'googlecheckout',
            'wpbusdirman_settings_config_35' => 'paypal-business-email',
            'wpbusdirman_settings_config_41' => 'paypal',
            'wpbusdirman_settings_config_42' => '2checkout-seller',
            'wpbusdirman_settings_config_43' => '2checkout',
            'wpbusdirman_settings_config_31' => 'featured-on',
            'wpbusdirman_settings_config_32' => 'featured-price',
            'wpbusdirman_settings_config_33' => 'featured-description',
            'wpbusdirman_settings_config_28' => 'recaptcha-public-key',
            'wpbusdirman_settings_config_29' => 'recaptcha-private-key',
            'wpbusdirman_settings_config_30' => 'recaptcha-on',
            'wpbusdirman_settings_config_49' => 'permalinks-directory-slug',
            'wpbusdirman_settings_config_50' => 'permalinks-category-slug',
            'wpbusdirman_settings_config_51' => 'permalinks-tags-slug'
        );
        return $option_translations;
    }

    public function upgrade_options() {
        if (!$this->settings)
            $this->_register_settings();

        $translations = $this->pre_2_0_options();

        if ($old_options = get_option('wpbusdirman_settings_config')) {
            foreach ($old_options as $option) {
                $id = strtolower($option['id']);
                $type = strtolower($option['type']);
                $value = $option['std'];

                if ($type == 'titles' || $id == 'wpbusdirman_settings_config_25' || empty($value))
                    continue;

                if ($id == 'wpbusdirman_settings_config_40') {
                    $this->set('googlecheckout', $value == 'yes' ? false : true);
                } elseif ($id == 'wpbusdirman_settings_config_41') {
                    $this->set('paypal', $value == 'yes' ? false : true);
                } elseif ($id == 'wpbusdirman_settings_config_43') {
                    $this->set('2checkout', $value == 'yes' ? false : true);
                } else {
                    if (!isset($this->settings[$translations[$id]]))
                        continue;

                    $newsetting = $this->settings[$translations[$id]];

                    switch ($newsetting->type) {
                        case 'boolean':
                            $this->set($newsetting->name, $value == 'yes' ? true : false);
                            break;
                        case 'choice':
                        case 'text':
                        default:
                            $this->set($newsetting->name, $value);
                            break;
                    }
                }

            }

            delete_option('wpbusdirman_settings_config');
        }
    }
}
