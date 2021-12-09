<?php

interface SignerInterface
{
    /**
     * Sign the given request with the given set of credentials and returns the
     * signed authorization string.
     *
     * @param $credentials array the credentials to sign the request with.
     * @param $httpMethod string
     * @param $path string
     * @param $headers array
     * @param $params array
     * @param $options array the options for signing.
     * @return string The signed authorization string.
     */
     public function sign(
        array $credentials,
        $httpMethod,
        $path,
        $headers,
        $params,
        $options = array()
    );
}
