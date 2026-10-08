UPDATE requests r
INNER JOIN customers c ON r.customer_id = c.id
SET r.client_id = c.upgraded_to_client_id,
    r.customer_id = NULL,
    r.requester_type = 'client'
WHERE c.upgraded_to_client_id IS NOT NULL
  AND r.requester_type = 'customer'
  AND r.customer_id IS NOT NULL;
