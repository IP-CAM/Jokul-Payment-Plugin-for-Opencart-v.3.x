# Jokul OpenCart Plugin

Jokul makes it easy for you accept payments from various channels. Jokul also highly concerned the payment experience for your customers when they are on your store. With this plugin, you can set it up on your OpenCart website easily and make great payment experience for your customers.
​
## Requirement
- OpenCart v3.0.3 or higher. This plugin is tested with OpenCart v3.0.3.6
- PHP v5.4 or higher
- MySQL v5.0 or higher
- Jokul account:
    - For testing purpose, please register to the Sandbox environment and retrieve the Client ID & Secret Key. Learn more about the sandbox environment [here](https://jokul.doku.com/docs/docs/getting-started/explore-sandbox)
    - For real transaction, please register to the Production environment and retrieve the Client ID & Secret Key. Learn more about the production registration process [here](https://jokul.doku.com/docs/docs/getting-started/register-user)
​
## DOKU Opencart Already Supported `doku_log`
​
This `doku_log` is useful to help simplify the process of checking if an issue occurs related to the payment process using the DOKU Plugin. If there are problems or problems using the plugin, you can contact our team by sending this doku_log file. `Doku_log` will record all transaction processes from any channel by date.

​
## How to use and take doku_log file?
​
1. Open your `opencart` directory on your store's webserver.
2. Create folder `doku_log` in your directory store's, so plugin will automatically track log in your store's webserver.
3. Then check `doku_log` and open file in your store's webserver.
4. You will see `doku log` file by date.
5. And you can download the file. 
6. If an issue occurs, you can send this `doku_log` file to the team to make it easier to find the cause of the issue.

## Payment Channels Supported
1. Virtual Account:
    - BCA VA
    - Bank Mandiri VA
    - Bank Syariah Indonesia VA
    - DOKU VA
2. Alfamart O2O
3. Credit Card
## How to Install
1. Download this repo
2. Copy all the source code `Jokul` folder into your `OpenCart root folder` and merge it.
3. Go to OpenCart Administration Page, choose Menu `Extensions` >  `Extensions`.
4. Choose `Payment` Filter
5. Scroll down and Choose `Jokul - General Configuration`
6. Click `Install` and you can configure the Plugin. 
​
## Plugin Usage
### General Configuration
1. Login to your OpenCart Admin Panel
2. Click `Extensions` > `Extensions`
3. Choose `Payment` Filter
4. You will find "Jokul - General Configuration"
5. Click `Enabled` and Edit the Plugin
6. Here is the fileds that you required to set:
​
    ![General Configuration](https://i.ibb.co/k8ZmWzp/Screen-Shot-2021-03-24-at-20-19-45.png)
    
    - **Environment**: For testing purpose, select Sandbox. For accepting real transactions, select Production
    - **Client ID**: Client ID you retrieved from the Sandbox / Production environment Jokul Back Office
    - **Secret Key**: Secret Key you retrieved from the Sandbox / Production environment Jokul Back Office
    - **Notification URL**: Copy this URL and paste the URL into the Jokul Back Office. Learn more about how to setup Notification URL [here](https://jokul.doku.com/docs/docs/after-payment/setup-notification-url)
7. Click Save Config button
8. Go Back to Payments Tab
9. Now your customer should be able to see the payment channels and you start receiving payments
​
### VA Configuration

![VA Configuration](https://i.ibb.co/cN0W4MZ/Screen-Shot-2021-03-24-at-20-26-21.png)
​
To enable VA, you will need to enable each of them:

1. In the `Extensions` > `Payment`
2. Click Enable on `Jokul - {{Channel Name}}`. For example: `Jokul - BCA VA`
3. You can configure how you display the channel to your customers.

​
To show the VA options to your customers, select each payment channel.
​
![VA Configuration Details](https://i.ibb.co/8M3HGn3/Screen-Shot-2021-03-24-at-20-31-02.png)
​
You can also edit how the VA channels will be shown to your customers by inputing below:  
​
- **Payment Channel Name Label**: Input the display name. This title will be visible in your store view

### Credit Card Configuration

![Credit Card Configuration](https://i.ibb.co/djTnq3X/Screen-Shot-2021-06-08-at-12-10-51.png)

To show the Credit Card options to your customers, simply toggle the channel that you wish to show.

![Credit Card Configuration Details](https://i.ibb.co/RbF82V4/Screen-Shot-2021-06-08-at-12-12-34.png)

You can also click Manage to edit how the Credit Card channels will be shown to your customers by clicking the Manage button.

### Alfamart O2O Configuration

![Alfamart O2O Configuration](https://i.ibb.co/djTnq3X/Screen-Shot-2021-06-08-at-12-10-51.png)

To show the Alfamart O2O options to your customers, simply toggle the channel that you wish to show.

![Alfamart O2O Configuration Details](https://i.ibb.co/LYzTyMr/Screen-Shot-2021-06-08-at-12-13-40.png)

You can also click Manage to edit how the Alfamart O2O channels will be shown to your customers by clicking the Manage button.
