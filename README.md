# leopards-certificate
Developed by the CMU Africa/MCF Leopards Capstone Team, this system streamlines the process of generating badges and certificates for learners while offering robust email templating features. It is designed to enhance administrative efficiency and user experience by automating key tasks related to badge and certificate distribution.

Key Features:
1. Automated Badge and Certificate Generation: Quickly create and distribute digital badges and certificates to learners with ease.
2. Advanced Email Templating: Utilize customizable templates to send professional and consistent communications.

## How to set up
### Prerequisites
Before you begin, ensure you have the following:

- A Google Cloud account.
- Basic knowledge of terminal commands.
- Sudo privileges on your VM.

### Step 1: Setting Up Google Cloud VM

1. Log in to Google Cloud Platform (GCP):
- Go to the Google Cloud Console and log in with your Google account.

2. Create a new project:
Click on the “New Project” button and follow the prompts to create a new project.

3. Deploy a new VM instance:
- Navigate to the Compute Engine and click on “Create Instance”.
- Choose the machine type and OS. We recommend using a Debian or Ubuntu image.
- Allow HTTP/HTTPS traffic under the firewall settings.
- Click “Create” to deploy your VM.
4. Connect to your VM:
Once your VM is set up, connect to it using SSH directly from the browser or through your terminal.

### Step 2: Installing XAMPP

1. Download XAMPP:
- Run the following command to download XAMPP for Linux:
~~~bash
wget https://www.apachefriends.org/xampp-files/8.0.x/xampp-linux-x64-8.0.x-0-installer.run
~~~

2. Make the installer executable:
~~~bash
sudo chmod +x xampp-linux-x64-8.0.x-0-installer.run
~~~

3. Run the installer:
~~~bash
sudo ./xampp-linux-x64-8.0.x-0-installer.run
~~~

4. Manage Services:
- You can start, stop, and check the status of XAMPP services using:
~~~bash
sudo /opt/lampp/lampp start
~~~

### Step 3: Configuring msmtp
1. Install msmtp:
~~~bash
sudo apt-get install msmtp msmtp-mta
~~~
2. Configure msmtp:
- Create or edit the msmtp configuration file:
~~~bash
sudo nano /etc/msmtprc
~~~
- Add the following configuration, adjusting as necessary for your email provider:
~~~bash
defaults
auth on
tls on
tls_trust_file /etc/ssl/certs/ca-certificates.crt

account default
host smtp.example.com
port 587
user your-email@example.com
password your-password
from your-email@example.com
logfile /var/log/msmtp.log
~~~
3. Set permissions for the config file:
~~~bash
sudo chmod 600 /etc/msmtprc
~~~
4. Test msmtp:
- Send a test email:
~~~bash
echo "This is a test email from msmtp." | msmtp recipient@example.com
~~~
### Step 4: Finalizing the Setup
1. Clone this repo into the directory /opt/lampp/lampp/htdocs with
~~~bash
cd /opt/lampp/htdocs
sudo git clone git@github.com:henrw/leopards-certificate.git
~~~
2. Import the SQL file learners.sql in the MyAdmin Interface
3. Restart XAMPP Services:
~~~bash
sudo /opt/lampp/lampp stop
sudo /opt/lampp/lampp start
~~~
4. Verify your setup:
- Ensure that your PHP server is running by accessing your VM’s IP in a web browser.
- Check /var/log/msmtp.log for any logs related to your test email to ensure msmtp is configured correctly.

  
