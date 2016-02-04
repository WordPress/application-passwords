
# Application Passwords

![](https://cldup.com/50AyeqtUEk.png)

This is a feature plugin that is a spinoff of the main Two-Factor Authentication plugin, found at https://github.com/georgestephanis/two-factor/.  With Application Passwords you are able to authenicate a user without providing his or her password directing, instead you will use a base64 encoded string of their username and a new application password.

# Create a New Password and Use it

1. Go to your User Profile page.
2. Scroll down until you see the Application Passwords section.
3. Type in a new password name (only used for describing a password, not for authenticating) and hit Add New.
4. After you create the password you will see it displayed direclty below the textfield.  Copy this password and save it safely somewhere since it will not be shown to you again.
5. Now that you have your new password you can base64 encode it via the terminal with your username to create a new acces token.
 
```shell 
echo -n "username:password" | base64
```

6. Once you have your access token you can then make a simple REST API call via the terminal to update a post.  Since the you are performing a POST request you will need to authorize yourself which is where you will use this access token.  You will see the post will update its title to "New Title".

```shell 
curl --header "Authorization: Basic ${ACCESS_TOKEN}" -X POST -d "title=New Title" http://localhost:8888/99robots/wp-json/wp/v2/posts/${post_id}
```
