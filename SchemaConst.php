<?php
class SchemaConst
{
    const HTTP = "http";
    const HTTPS = "https";
    const WEBSOCKET = "ws";
    const RESP = "resp";

    public static function isHttpOrSecurity() {
        return self::HTTP === Config::get("application.server.schema")
            || self::HTTPS === Config::get("application.server.schema");
    }

    public static function isWebSocket() {
        return self::WEBSOCKET === Config::get("schema");
    }
}