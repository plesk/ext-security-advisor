[![Apache 2](http://img.shields.io/badge/license-Apache%202-blue.svg)](http://www.apache.org/licenses/LICENSE-2.0)
[![Join the chat at https://gitter.im/plesk/ext-security-advisor](https://badges.gitter.im/plesk/ext-security-advisor.svg)](https://gitter.im/plesk/ext-security-advisor?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

# Security Advisor

The Plesk Security Advisor solves three important aspects of running web sites:

   * setup free SSL certificates for all domains with Let's Encrypt (existing certificates will be kept)
   * switch all WordPress sites to https only by changing urls in WordPress and adding redirect rules to .htaccess
   * switch all websites to HTTP/2 for improved security and performance
   * scan server for vulerabilities and report them
   * offer updates for outdated packages

## How things started

When speaking to a lot of WordPress users on WordPress meet-ups and WordCamps, it turned out that everybody knows that their sites should use SSL only, but many Wordpress users do not know how to actually configure their server correctly. For sure, there are tons of blog articles describing how to switch your WordPress site to https by using the plugin "Better Search and Replace" - but honestly, that has to be easier!
And configuring Let's Encrypt for several domains is complicated and effort that nobody wants to do manually. Why not just turn it on with one click?
That was our motivation for creating an extension for Plesk at the WHD.hackathon of the largest hosting conference "World Hosting Days".
And some colleagues from Opsani were so excited about the idea that they directly joined our hackathon team and suggested adding a vulnerability scan to make Plesk users aware of vulnerabilities and mitigations.

This is how we started! 

If you want to contribute, just do a pull request and we'll review your changes. 

We are happy for every contributor since we care a lot for making the internet a safer place!

## Contributing

Use the virtual machine with the prepared Plesk installation:
```
vagrant up
```

Login URL: http://localhost:8880

Credentials: admin / changeme
