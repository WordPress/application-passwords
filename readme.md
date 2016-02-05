
# Application Passwords

![](https://cldup.com/50AyeqtUEk.png)

This is a feature plugin that is a spinoff of the main Two-Factor Authentication plugin, found at https://github.com/georgestephanis/two-factor/.  With Application Passwords you are able to authenticate a user without providing that user's password directly, instead you will use a base64 encoded string of their username and a new application password.

# Create a New Password

1. Go to your User Profile page.
2. Scroll down until you see the Application Passwords section.
3. Type in a new password name (only used for describing a password, not for authenticating) and hit Add New.
4. After you create the password you will see it displayed directly below the textfield.  Copy this password and save it safely somewhere, since it will not be shown to you again.

# Testing Password

This test uses the technologies listed below, but you can use any REST API or XMLRPC request.

* WordPress REST API
* cURL
* OS X
* terminal
* local development environment (e.g. MAMP) running on localhost

1. Now that you have your new password you can base64 encode it via the terminal with your username to create a new access token.
 
```shell 
echo -n "username:password" | base64
```

2. Once you have your access token you can then make a simple REST API call via the terminal to update a post.  Since you are performing a POST request you will need to authorize the request using this access token. If authorized correctly, you will see the post title update to "New Title."

```shell 
curl --header "Authorization: Basic ${ACCESS_TOKEN}" -X POST -d "title=New Title" http://localhost:8888/wp-json/wp/v2/posts/${post_id}
```
