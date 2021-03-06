<?php

    /**
     * @package utils.net.SMTP.Client.Connection.State
     * @author Andrey Knupp Vital <andreykvital@gmail.com>
     * @filesource utils\net\SMTP\Client\Connection\State\Connected.php
     */
    namespace utils\net\SMTP\Client\Connection\State;
    use utils\net\SMTP\Client\AbstractConnectionState;
    use utils\net\SMTP\Client\Connection;
    use utils\net\SMTP\Client\Message;
    use \Exception;
    use \LogicException;
    use \ErrorException;
    use utils\net\SMTP\Client\CommandInvoker;
    use utils\net\SMTP\Client\Command\QUITCommand;

    abstract class Connected extends AbstractConnectionState
    {

        /**
         * Reads a server reply.
         * @return Message
         */
        public function read()
        {
            while (!feof($this->stream)) {
                $message = new Message(stream_get_line($this->stream, 515, Message::EOL));
                $this->messages[] = $message;

                if (substr($message->getFullMessage(), 3, 1) === chr(32)) {
                    $this->lastMessage = $message;
                    return $message;
                }
            }
        }

        /**
         * Writes data on the server stream.
         * @param string $data the data to be written
         * @return integer
         */
        public function write($data)
        {
            $message = new Message($data);
            $this->messages[] = $message;
            $this->lastMessage = $message;
            return fwrite($this->stream, $data . Message::EOL);
        }

        /**
         * Closes the connection with SMTP server
         * @param Connection $context the connection context
         * @return void
         */
        public function close(Connection $context)
        {
            $invoker = new CommandInvoker();
            $invoker->invoke(new QUITCommand($context));
            
            if (fclose($this->stream)) {
                $this->stream = NULL;
                $this->changeState(new Closed(), $context);
            }
        }

    }