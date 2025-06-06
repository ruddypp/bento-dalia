# Pesanan Barang System

This document describes how the Pesanan Barang (Order Items) system works in the inventory management application.

## Overview

The Pesanan Barang system allows you to create orders for items from suppliers. When an order is processed, the items are added to the Bahan Baku (Raw Materials) system with a pending status. Once approved in the Bahan Baku system, the items are added to inventory and the order status is updated accordingly.

## Status Flow

1. **Pending (Menunggu)**: Initial status when an order is created
2. **Diproses**: Status when at least one item in the order has been approved in Bahan Baku
3. **Selesai**: Status when all items in the order have been approved in Bahan Baku
4. **Dibatalkan**: Status when the order has been canceled

## How to Use

### Creating a New Order

1. Go to `pesan_barang.php`
2. Click "Tambah Pesanan" button
3. Select a supplier from the dropdown
4. Enter the order date
5. Add items to the order by selecting them from the dropdown
6. Enter quantity, period, price, and location for each item
7. Click "Simpan Pesanan" to create the order

### Processing an Order

When an order is created, it has a "Pending" status. To process the order:

1. Go to `bahan_baku.php`
2. Find the items that were created from the order (they will have a reference to the order in the "Catatan Retur" field)
3. Approve each item by changing its status to "Approved"
4. As you approve items, the order status will automatically change:
   - When at least one item is approved: Order status changes to "Diproses"
   - When all items are approved: Order status changes to "Selesai"

### Viewing Order Details

1. Go to `pesan_barang.php`
2. Click the eye icon next to an order to view its details
3. The modal will show all information about the order, including items, quantities, and prices

## Technical Implementation

The synchronization between Pesanan Barang and Bahan Baku is handled by a database trigger:

- When a Bahan Baku item is updated to "Approved" status, the trigger checks if it's part of an order
- If it is, the trigger updates the order status based on whether all items are approved or not

The trigger is defined in `database/sync_pesanan_bahan_baku.sql` and can be installed using the `install_triggers.php` script.

## Testing

You can test the system using the following scripts:

- `test_trigger.php`: Checks if the trigger is properly installed and shows the status of orders and items
- `test_update_bahan_baku.php`: Allows you to manually update Bahan Baku items and see the effect on order status

## Troubleshooting

If the order status doesn't update automatically when approving items in Bahan Baku:

1. Check if the trigger is properly installed by running `test_trigger.php`
2. Verify that the Bahan Baku items have a reference to the order in the "Catatan Retur" field
3. Try manually updating the items using `test_update_bahan_baku.php` 