<?php
include "packet_class.php";

/**
 * Defines the server address to be used by the application.
 *
 * This constant holds the address of the server, typically used
 * for database connections or other server-related configurations.
 * The value is set to "localhost", which refers to the local machine.
 *
 * @constant string SERVER_ADDRESS The server address.
 */
const SERVER_ADDRESS = "localhost";
/**
 * Constant SERVER_PORT
 *
 * Represents the port number used by the server.
 * This constant is typically used to define the port
 * on which the server is configured to listen for
 * incoming connections.
 *
 * @var int
 */
const SERVER_PORT = 29300;

if ($argc < 4) {
    echo "Usage: php broadcast.php <roleid> <channelid> <message>\n";
    exit(1);
}

/**
 * Combines elements of an array into a single string, starting from a specified index.
 *
 * @param array $argv An array containing the elements to be combined.
 * @param int $startIndex The index in the array from which to start combining elements.
 * @return string The combined string created from the specified elements of the array.
 */
function combineMessageParts(array $argv, $startIndex)
{
    $messageParts = array_slice($argv, $startIndex);
    return implode(' ', $messageParts);
}

/**
 * Represents the unique identifier associated with a specific role.
 *
 * This variable is used to distinguish and reference roles within a system.
 * Each role is assigned a unique ID, which can be utilized in processes
 * such as authentication, authorization, or assigning permissions to users.
 *
 * @var int|string The unique identifier for the role, represented as an integer or string.
 */
$roleid = (int)$argv[1];
/**
 * Represents the unique identifier for a communication channel.
 *
 * This variable is used to store the ID associated with a specific channel,
 * which can be used for purposes such as identifying, managing, or interacting
 * with the channel within the application.
 *
 * @var int|string The unique identifier for the channel. It can be an integer or
 *                 a string, depending on the system's requirements or implementation.
 */
$channelid = (int)$argv[2];
/**
 * The $message variable is used to store a text message or string.
 *
 * It acts as a container for holding textual data, which may include notifications,
 * user-visible messages, debugging information, or any other type of string content.
 *
 * This variable can be dynamically updated or manipulated during runtime,
 * depending on the application's requirements or user input.
 */
$message = combineMessageParts($argv, 3);

/**
 * A variable representing the broadcast message or transmission
 * targeted for chat systems or participants in a chat session.
 *
 * The chatBroadcast variable may be used to send or manage
 * messages that are intended to be distributed to multiple
 * users simultaneously within a chat system or application.
 *
 * This could involve live updates, notifications, or any other
 * information that should be shared with all participants or a
 * predefined group in real time.
 *
 * It is essential to handle the data within this variable carefully
 * to ensure that sensitive or irrelevant information is not
 * inadvertently broadcasted.
 *
 * Note: Ensure proper validation and security for any data passed
 * into or through this variable to mitigate risks of misuse or
 * unintended behavior.
 */
$chatBroadcast = new WritePacket();
$chatBroadcast->writeUByte($channelid);  // Channel
$chatBroadcast->writeUByte(0);            // Emotion (default 0)
$chatBroadcast->writeUInt32($roleid);    // Roleid
$chatBroadcast->writeUString($message);  // Text message
$chatBroadcast->writeOctets("");          // Data (empty)
$chatBroadcast->pack(0x78);               // Opcode

$chatBroadcast->send(SERVER_ADDRESS, SERVER_PORT);
