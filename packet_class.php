<?php

/**
 * The ReadPacket class is used to parse binary data by sequentially reading various data types
 * from the provided packet data. It provides methods to read primitive types, encoded values,
 * and strings, while maintaining the current position in the data stream.
 */
class ReadPacket
{
    /**
     *
     */
    public $data = '';
    /**
     *
     */
    public $pos = 0;

    /**
     * Constructor method to initialize the object with provided data.
     *
     * @param object|null $obj An optional object that contains a 'response' property to initialize the data.
     * @return void
     */
    function __construct($obj = null)
    {
        if ($obj !== null && isset($obj->response)) {
            $this->data = $obj->response;
        }
    }

    /**
     * Reads and unpacks data from the current position in the binary data stream.
     *
     * @param string $format The unpack format to read the binary data, following PHP's unpack format codes.
     * @param int $length The number of bytes to read from the binary data.
     *
     * @return mixed The unpacked value extracted from the binary data.
     */
    private function readAndUnpack($format, $length)
    {
        $value = unpack($format, substr($this->data, $this->pos, $length));
        $this->pos += $length;
        return $value[1];
    }

    /**
     * Reads a specified number of bytes from the data starting at the current position.
     *
     * @param int $length The number of bytes to read.
     * @return string Returns the read bytes as a string.
     */
    public function readBytes($length)
    {
        $value = substr($this->data, $this->pos, $length);
        $this->pos += $length;
        return $value;
    }

    /**
     * Reads an unsigned byte from the data stream.
     *
     * This method extracts a single unsigned byte (8 bits) from the current
     * position in the stream and returns it as an integer.
     *
     * @return int The unsigned byte read from the stream.
     */
    public function readUByte()
    {
        return $this->readAndUnpack("C", 1);
    }

    /**
     *
     */
    public function readFloat()
    {
        // Reverse bytes for little-endian float unpacking
        $bytes = strrev(substr($this->data, $this->pos, 4));
        $this->pos += 4;
        $value = unpack("f", $bytes);
        return $value[1];
    }

    /**
     * Reads an unsigned 32-bit integer from the current data stream.
     *
     * This method reads 4 bytes from the data stream and unpacks them as a
     * big-endian unsigned 32-bit integer. It assumes the data stream is
     * already positioned correctly before the read operation.
     *
     * @return int The unsigned 32-bit integer value read from the stream.
     */
    public function readUInt32()
    {
        return $this->readAndUnpack("N", 4);
    }

    /**
     * Reads an unsigned 16-bit integer from the current position in the data stream.
     *
     * The method uses a big-endian byte order for interpreting the data.
     *
     * @return int The unsigned 16-bit integer read from the data stream.
     */
    public function readUInt16()
    {
        return $this->readAndUnpack("n", 2);
    }

    /**
     * Reads a specified number of octets from the internal data buffer.
     * The length of octets to read is determined by reading an unsigned integer value.
     * Advances the internal position pointer by the length of the read octets.
     *
     * @return string The read octets as a hexadecimal string.
     */
    public function readOctets()
    {
        $length = $this->readCUInt32();
        $value = unpack("H*", substr($this->data, $this->pos, $length));
        $this->pos += $length;
        return $value[1];
    }

    /**
     * Reads a UTF-16 encoded string from the current position in the data, converts it to UTF-8, and updates the position.
     *
     * @return string The converted UTF-8 string.
     */
    public function readUString()
    {
        $length = $this->readCUInt32();
        $value = iconv("UTF-16", "UTF-8", substr($this->data, $this->pos, $length)); // LE?
        $this->pos += $length;
        return $value;
    }

    /**
     * Reads and returns packet information including opcode and length.
     *
     * @return array An associative array containing the packet's 'Opcode' and 'Length', both represented as integers.
     */
    public function readPacketInfo()
    {
        return [
            'Opcode' => $this->readCUInt32(),
            'Length' => $this->readCUInt32()
        ];
    }

    /**
     * Moves the current position by a specified offset.
     *
     * @param int $offset The number of units to move the position. Can be positive or negative.
     * @return void
     */
    public function seek($offset)
    {
        $this->pos += $offset;
    }

    /**
     * Reads a custom unsigned 32-bit integer from the current data source.
     * The format of the integer is determined based on the value of the first byte read.
     *
     * @return int The interpreted 32-bit unsigned integer value.
     */
    public function readCUInt32()
    {
        $firstByte = $this->readUByte();

        switch ($firstByte & 0xE0) {
            case 0xE0:
                $value = $this->readAndUnpack("N", 4);
                break;
            case 0xC0:
                $segment = substr($this->data, $this->pos - 1, 4);
                $this->pos += 3;
                $value = unpack("N", $segment)[1] & 0x1FFFFFFF;
                break;
            case 0x80:
            case 0xA0:
                $segment = substr($this->data, $this->pos - 1, 2);
                $this->pos++;
                $value = unpack("n", $segment)[1] & 0x3FFF;
                break;
            default:
                $value = $firstByte;
                break;
        }

        return $value;
    }
}

/**
 * WritePacket class is utilized to construct and serialize packets using various data types.
 * It provides methods to write bytes, integers, floating-point numbers, and encoded strings
 * into a packet, and also facilitates sending the constructed packet to a socket.
 */
class WritePacket
{
    /**
     *
     */
    public $request = '';
    /**
     *
     */
    public $response = '';

    /**
     * Packs a value into a binary string according to a specified format and appends it to the request.
     *
     * @param string $format The format used for packing the data (as per the PHP pack function).
     * @param mixed $value The value to be packed.
     * @param bool $reverse Whether to reverse the packed data before appending. Defaults to false.
     * @return void
     */
    private function appendPacked($format, $value, $reverse = false)
    {
        $data = pack($format, $value);
        if ($reverse) {
            $data = strrev($data);
        }
        $this->request .= $data;
    }

    /**
     * Appends the provided byte data to the request string.
     *
     * @param string $value The byte data to be appended to the request.
     * @return void
     */
    public function writeBytes($value)
    {
        $this->request .= $value;
    }

    /**
     * Writes an unsigned byte to the internal request.
     *
     * @param int $value The unsigned byte value to write. Must be between 0 and 255.
     * @return void
     */
    public function writeUByte($value)
    {
        $this->appendPacked("C", $value);
    }

    /**
     * Writes a floating-point number to the internal request buffer.
     *
     * @param float $value The floating-point number to be written.
     * @return void
     */
    public function writeFloat($value)
    {
        $this->appendPacked("f", $value, true);
    }

    /**
     * Writes an unsigned 32-bit integer to the internal request buffer.
     *
     * @param int $value The unsigned 32-bit integer to write.
     * @return void
     */
    public function writeUInt32($value)
    {
        $this->appendPacked("N", $value);
    }

    /**
     * Writes an unsigned 16-bit integer to the internal request buffer.
     *
     * @param int $value The unsigned 16-bit integer to write.
     * @return void
     */
    public function writeUInt16($value)
    {
        $this->appendPacked("n", $value);
    }

    /**
     * Writes a sequence of octets to the current request. If the provided value is a hexadecimal string,
     * it will be converted to binary data before being written.
     *
     * @param string $value The octet string or hexadecimal string to write.
     * @return void
     */
    public function writeOctets($value)
    {
        if (ctype_xdigit($value)) {
            $value = pack("H*", $value);
        }
        $this->writeCUInt32(strlen($value));
        $this->writeBytes($value);
    }

    /**
     * Encodes a given string in a specified character encoding and writes it to the request.
     *
     * @param string $value The input string to be encoded and written.
     * @param string $coding The character encoding to use for the string. Defaults to "UTF-16LE".
     * @return void
     */
    public function writeUString($value, $coding = "UTF-16LE")
    {
        $encodedValue = iconv("UTF-8", $coding, $value);
        $this->writeCUInt32(strlen($encodedValue));
        $this->writeBytes($encodedValue);
    }

    /**
     * Packs the provided value into the request with specific formatting.
     *
     * @param mixed $value The value to be packed.
     * @return void
     */
    public function pack($value)
    {
        $this->request = $this->cUInt($value) . $this->cUInt(strlen($this->request)) . $this->request;
    }

    /**
     *
     * @return string Returns a combination of the unsigned integer representation of the request length and the request itself.
     */
    public function unmarshal()
    {
        return $this->cUInt(strlen($this->request)) . $this->request;
    }

    /**
     * Sends a request to a specified address and port using a socket connection.
     *
     * @param string $address The IP address or hostname of the destination server.
     * @param int $port The port number of the destination server.
     * @return bool Returns true if the request was successfully sent and a response was received, false otherwise.
     */
    public function send($address, $port)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return false;
        }

        if (socket_connect($socket, $address, $port)) {
            socket_set_block($socket);
            socket_send($socket, $this->request, strlen($this->request), 0);
            socket_recv($socket, $this->response, 131072, 0);
            socket_set_nonblock($socket);
            socket_close($socket);
            return true;
        } else {
            socket_close($socket);
            return false;
        }
    }

    /**
     * Appends a 32-bit unsigned integer encoded in a custom format to the request.
     *
     * @param int $value The 32-bit unsigned integer to encode and append.
     * @return void
     */
    public function writeCUInt32($value)
    {
        $this->request .= $this->cUInt($value);
    }

    /**
     * Encodes an unsigned integer into a binary representation based on its value range.
     *
     * @param int $value The unsigned integer to be encoded.
     * @return string The binary encoded representation of the provided integer.
     */
    private function cUInt($value)
    {
        if ($value <= 0x7F) {
            return pack("C", $value);
        } elseif ($value <= 0x3FFF) {
            return pack("n", ($value | 0x8000));
        } elseif ($value <= 0x1FFFFFFF) {
            return pack("N", ($value | 0xC0000000));
        } else {
            return pack("C", 0xE0) . pack("N", $value);
        }
    }
}
?>