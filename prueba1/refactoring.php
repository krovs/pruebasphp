<?php

class Product 
{
    public static function stock($productId, $quantityAvailable, $cache = false, $cacheDuration = 60, $securityStockConfig = null) {
        
        // Obtenemos el stock bloqueado por pedidos en curso
        $ordersQuantity = self::getOrdersQuantity($productId, $cache, false);

        // Obtenemos el stock bloqueado
        $blockedStockQuantity = self::getOrdersQuantity($productId, $cache, true);

        // Si existe stock, se devuelve y si no, se devuelve lo que contenga, pudiendo ser negativo
        if ($quantityAvailable >= 0) {
            $quantityToreturn = 0; // almacena el stock final a devolver

            // Si existe stock bloqueado se calcula, si no, se devuelve el disponible o 0
            if (isset($ordersQuantity) || isset($blockedStockQuantity)) {
                $quantityToreturn = self::zeroOrMore($quantityAvailable - @$ordersQuantity - @$blockedStockQuantity);
            } 
            else {
                $quantityToreturn = self::zeroOrMore($quantityAvailable);
            }

            // Si la configuracion de seguridad existe se aplica al stock final
            if (!empty($securityStockConfig)) {
                $quantityToreturn = self::applySec($quantityToreturn, $securityStockConfig);
            }

            return $quantityToreturn;
        }
        else {
            return $quantityAvailable;
        }

        return 0;
    }

    // Funcion auxiliar que convierte a 0 valores negativos
    private static function zeroOrMore($q) {
        return $q > 0 ? $q : 0;
    }

    // Funcion que devuelve el stock de las lineas de pedido
    private static function getOrderLine($id) {
        return OrderLine::find()
            ->select('SUM(quantity) AS quantity')
            ->joinWith('order')
            ->where("(order.status = '" . Order::STATUS_PENDING . "' OR order.status = '" . Order::STATUS_PROCESSING . "' 
                OR order.status = '" . Order::STATUS_WAITING_ACCEPTANCE . "') AND order_line.product_id = $id")
            ->scalar(); 
    }

    // Funcion que devuelve el stock bloqueado del carrito dependiendo de la cache
    private static function getBlockedStock($id, $datevar) {
        return BlockedStock::find()
            ->select('SUM(quantity) AS quantity')
            ->joinWith('shoppingCart')
            ->where("blocked_stock.product_id = $id AND $datevar > '" .  date('Y-m-d H:i:s') . "' 
                AND (shopping_cart_id IS NULL OR shopping_cart.status = '" . ShoppingCart::STATUS_PENDING . "')")
            ->scalar();
    }

    // Funcion que devuelve la cantidad del stock segun la cache y si esta bloqueado
    private static function getOrdersQuantity($id, $cache, $blocked) {
        if ($cache) {
            if (blocked) {
                return self::getOrderLine($id);
            }
            else {
                return self::getBlockedStock($id, "blocked_stock_to_date");
            }
        }
        else {
            if (blocked) {
                return OrderLine::getDb()->cache(function($db) use ($id) {
                    self::getOrderLine($id);
                }, $cacheDuration);
            }
            else {
                return BlockedStock::getDb()->cache(function($db) use ($id) {
                    self::getBlockedStock($id, "blocked_stock_date");
                }, $cacheDuration);
            }
        }
    }

    // Funcion auxiliar que Aplica la configuracion de seguridad
    function applySec($quant, $securityStockConfig) {
        return ShopChannel::applySecurityStockConfig(
            $quant,
            @$securityStockConfig->mode,
            @$securityStockConfig->quantity
        );
    }
}