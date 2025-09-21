-- Create system_settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT,
    `type` ENUM('text', 'textarea', 'email', 'number', 'color', 'image', 'url') NOT NULL DEFAULT 'text',
    `label` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `category` VARCHAR(50) DEFAULT 'general',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `system_settings` (`key`, `value`, `type`, `label`, `description`, `category`) VALUES
-- Site Information
('site_title', 'Your E-Commerce Store', 'text', 'Site Title', 'The name of your website', 'general'),
('site_description', 'Your one-stop shop for quality products', 'textarea', 'Site Description', 'A brief description of your website', 'general'),
('site_keywords', 'ecommerce, online shopping, products', 'textarea', 'Site Keywords', 'SEO keywords (comma-separated)', 'general'),
('site_logo', 'assets/images/logo.png', 'image', 'Site Logo', 'Your website logo', 'general'),
('site_favicon', 'assets/images/favicon.ico', 'image', 'Site Favicon', 'Your website favicon', 'general'),

-- Contact Information
('company_name', 'Your Company Name', 'text', 'Company Name', 'Your registered business name', 'contact'),
('company_address', 'Your Company Address', 'textarea', 'Company Address', 'Your physical business address', 'contact'),
('contact_email', 'contact@example.com', 'email', 'Contact Email', 'Primary contact email address', 'contact'),
('phone_number', '+1234567890', 'text', 'Phone Number', 'Primary contact phone number', 'contact'),
('business_hours', 'Mon-Fri: 9AM-6PM', 'text', 'Business Hours', 'Your operating hours', 'contact'),

-- Social Media Links
('facebook_url', '', 'url', 'Facebook URL', 'Your Facebook page URL', 'social'),
('twitter_url', '', 'url', 'Twitter URL', 'Your Twitter profile URL', 'social'),
('instagram_url', '', 'url', 'Instagram URL', 'Your Instagram profile URL', 'social'),
('linkedin_url', '', 'url', 'LinkedIn URL', 'Your LinkedIn page URL', 'social'),

-- Footer Content
('footer_about', 'Brief description about your company for the footer', 'textarea', 'Footer About Text', 'Short description shown in footer', 'footer'),
('copyright_text', '© 2025 Your Company. All rights reserved.', 'text', 'Copyright Text', 'Copyright notice in footer', 'footer'),

-- SEO & Analytics
('google_analytics_id', '', 'text', 'Google Analytics ID', 'Your Google Analytics tracking ID', 'analytics'),
('meta_robots', 'index, follow', 'text', 'Meta Robots', 'Default robots meta tag content', 'seo'),

-- Contact Form
('contact_form_email', '', 'email', 'Contact Form Email', 'Email where contact form submissions are sent', 'contact'),
('contact_success_msg', 'Thank you for your message. We will get back to you soon!', 'textarea', 'Contact Success Message', 'Message shown after successful contact form submission', 'contact')

ON DUPLICATE KEY UPDATE 
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `description` = VALUES(`description`),
    `category` = VALUES(`category`);