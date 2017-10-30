Magento 2 to Ogoship integration module.

Installation Instructions
--------------------------
Follow the below steps to install the extension

1) Open the ZIP file and extract the contents of the the folder.
2) Upload the files under root folder
3) Run below command one by one in root folder
	1. php bin/magento cache:clean
	2. php bin/magento indexer:reindex
	3. php bin/magento setup:upgrade
	4. php -dmemory_limit=6G bin/magento setup:static-content:deploy
	
3) Login to admin
4) Browse Store -> Configuration->General and click Ogoship button Configuration.
7) Change Status  Deny product export to Ogoship  and Deny latest changes
8) Add Merchent Id and Secret Token.
