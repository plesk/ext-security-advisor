# 1.6.0 (28 November 2017)

* [+] Security Advisor is now available for Windows.
* [+] Each domain on the "Domains" tab now has a **Secure** button, allowing users to install a free or paid Symantec SSL/TLS certificate on the domain. This can be turned off with the following setting in `panel.ini`:

      [ext-security-advisor]
      promoteSymantec = false
      
* [+] Customers and resellers can now use the following tabs in Security Advisor:
    * Domains tab, showing domains not secured with an SSL certificate.
    * Wordpress tab, showing instances without HTTPS enabled.
* [*] Each domain name in the list of domains now has a link to the domain details page.
* [*] On the "WordPress" tab, each instance in the list now has a link to instance details page.
* [*] The "Domains" tab now also shows IDN domains.
* [-] Detached WordPress instances were indeed present on the "WordPress" tab. (EXTPLESK-350)
* [-] "Valid from" and "Valid to" dates of Certificate Authority were shown instead of dates of the SSL/TLS certificate. (EXTPLESK-315)
* [-] When customers issued Let's Encrypt certificates from Security Advisor, administrator's email was passed to Let's Encrypt, which resulted in customers unable to receive notifications about expired or compromised domain SSL/TLS certificates. Now customers' emails are passed. (EXTPLESK-345)
* [-] URL for WordPress instance installed on an IDN domain was shown encoded in Punycode, making it harder for user to match domain and WordPress instance. (EXTPLESK-334)

# 1.5.0 (28 September 2017)

* [+] The list of domains in the Security Advisor can now be filtered.
* [+] The subscription screen now has a Security Advisor button, which opens Security Advisor with a filtered list of domains for the corresponding subscription.
* [+] The Home screen now has a Security Advisor button, which opens Security Advisor with a list of all available domains.
* [*] The DataGrid extension was renamed to Opsani, because the product name has changed.
* [*] The ReadyKernel extension will not be suggested to install, because Virtuozzo has announced that it stops supporting ReadyKernel for RedHat Enterprise Linux, CentOS and Ubuntu.

# 1.4.1 (11 July 2017)

* [!] This update contains changes, affecting both Let's Encrypt and Security Advisor extensions. Please also update Let's Encrypt to version 2.2.0 or later.
* [*] Now Security Advisor delegates obtaining free certificates for securing Plesk Panel from Let's Encrypt CA to the corresponding Plesk Let's Encrypt extension, which excels in this task.

# 1.4.0 (03 March 2017)

* [+] The System tab now displays the promos of Virtuozzo ReadyKernel and KernelCare and allows installation of one of those tools.
