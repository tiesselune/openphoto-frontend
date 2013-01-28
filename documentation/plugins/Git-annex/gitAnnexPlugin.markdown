# OpenPhoto / Using Git-annex Plugin

#### Git-annex is an open-source backend for managing your photos.

## What can you do with this?

Git-annex allows you to manage the storage of your photos so that they are, or *not*, hosted on your open-photo server, just like dropbox. Once you uploaded a file, you can move it freely and independantly of the others on your Amazon S3 account, on your rsync.net account, or on your personnal laptop, and git-annex will get it for you when open-photo needs it.

## Installation of the plugin

1. Install git-core and git-annex on your server, and make sure it is set up with a Local file-system backend. *Git-annex is available for most of the main linux distributions and for mac.*

2. Just click the `Manage` button on the top of the status bar, then choose `Applications`.

3. In the list of the plugins, activate `Git-Annex`.

*Congrats!* You're done. Git-annex will now add every photo you upload to your "photo" repository, and you will be able to manage them with git-annex and/or git-annex-assistant. Please refer to the [git-annex documentation](http://git-annex.branchable.com/) for more details.

## Now what?

To be fully able to use git-annex to manage your photos, your will have to:

* Configure special remotes

* Configure a client

### Configuring a special Remote

Please refer to the [git-annex documentation](http://git-annex.branchable.com/) for more details.

### Configuring a client.

From your server, configure git to be usable as a git server, replacing `myuser` and `server.com` by your own values:

	ssh myuser@server.com mkdir .ssh
	scp ~/.ssh/id_rsa.pub myuser@server.com:.ssh/authorized_keys
	sudo adduser git
	sudo mkdir /home/git/.ssh
	sudo cp ~/.ssh/authorized_keys /home/git/.ssh/
	sudo chown -R git:git /home/git/.ssh
	sudo chmod 700 !$
	sudo chmod 600 /home/git/.ssh/*
	
To make cloning easier, add a symbolic link to your actual repository, replacing the path with the real path to your original directory in your photo directory (chosen at setup):

	sudo ln -s ~git/photo.git path/to/your/photo/directory/original
	sudo chown -R git:git ~git/photo.git
	
By default, it will be `/var/www/server.com/src/html/photos/original`, or wherever you placed your open-photo root followed by `/src/html/photos/original`
	
Now come back to your client and run:

	git clone git@server.com:photo.git
	
And that's it.

### How to upload a photo.

In your clone of the photo repository, add your pictures, then run:

	git annex add picture.jpg
	
This will add picture.jpg to the git-annex repository, and lock it safely.

	git annex move picture.jpg --to origin
	
This will send the actual content of the file on your server. To keep your own version of it on your client, run `git annex copy` instead of `move`.

	git annex sync
	
This will notify your open-photo server that new photos are available, and will upload them directly.

### A word of caution

`git annex sync` never moves pictures around. `git annex move` `git annex get` and `git annex copy` do that. `sync` only notifies other repositories that new files are added, and available.
Please note that if your picture is not on your server and the file is not available on any reachable repository (if the server can't pull from your client repo, for instance) when you run `git annex sync`, your photo won't be uploaded. 

To solve that problem, and still skip `git annex copy` or `git annex move`, you can make your client reachable by connecting it to a Jabber account, and pairing it with your server. Please refer to the [git-annex documentation](http://git-annex.branchable.com/) for more details.

That way, you can just as well use `git-annex-assistant` and let it add everything for you and sync it when a connection to the server is available.

### Internals

This plugin will add to your repository a branch `photoView`. Please note that the server must never checkout another branch, and that you should avoid modifying it manually, either from your client or server. It is meant to be used by the Apache user only.





