# stellar-event-publisher
Monitors the Stellar network and publishes events to webhooks

### Quickstart

1. Clone this project
    ```bash
    $ git clone https://github.com/zulucrypto/stellar-event-publisher.git
    $ cd stellar-event-publisher
    ```
    
1. Install dependencies
    ```bash
    $ composer install
    ```
    
    Accept the defaults for all options
    
1. Set up a webhook to receive events. https://requestb.in/ provides a quick and free way to test webhooks


1. Create a config file that will send all ledgers to the webhook:

    **var/config/send-ledgers.json**
    ```$json
    [
      {
        "source": "ledgers",
        "destination": "https://requestb.in/YOUR_BIN_HERE"
      }
    ]
    ```

1. Initialize the database
    ```bash
    $ bin/console doctrine:schema:create
    ```

1. Start the supervisor process which launches the watcher and webhook publisher

    ```bash
    $ bin/console sep:supervisor
    ```
    
1. Within 30 seconds ledger events should start being delivered to your webhook endpoint



### Docker Container

To run this project without checking out the source code, use the docker image


1. Set up a webhook to receive events. https://requestb.in/ provides a quick and free way to test webhooks

1. Create a directory to contain config files
    ```bash
    $ mkdir /tmp/sep-config-test
    ``` 

2. Create a config file that will send all ledgers to the webhook:

    **/tmp/sep-config-test/send-ledgers.json**
    ```$json
    [
      {
        "source": "ledgers",
        "destination": "https://requestb.in/YOUR_BIN_HERE"
      }
    ]
    ```

3. Use docker to run a stellar event publisher container that reads from the config directory

    ```bash
    $ docker run -v /tmp/sep-config-test:/project/var/config zulucrypto/stellar-event-publisher
    ``` 
    
    
 