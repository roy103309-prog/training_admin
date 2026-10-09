🍽️ Correct Restaurant Order Flow (Production Style)
🔥 Main Logic
Create Order
   ↓
Add Items
   ↓
Place Order
   ↓
Kitchen Display (KDS)
   ↓
Kitchen Prepare
   ↓
Ready to Serve
   ↓
Serve Order
   ↓
Move to Ongoing Payment List
   ↓
Add More Items (optional)
   ↓
Generate Final Bill
   ↓
Payment
   ↓
Complete Order
⚙️ UPDATED ORDER STATUS FLOW
🔹 Order Status
Draft
Pending Kitchen
Preparing
Ready To Serve
Served
Ongoing Payment
Paid
Completed
Cancelled
🍳 1. Kitchen Display System (VERY IMPORTANT)

This is the missing core.

🔹 Kitchen Display Features
Show ALL active order tokens

Each kitchen card shows:

Token No
Table / Takeaway
Items List
Item Notes
Order Time
Status
🔹 Example Kitchen Card
TOKEN: KOT-102

Table: T-05

2x Burger
1x Pizza
1x Coke

Note:
- Less spicy

[Preparing]
[Ready]
🔹 Kitchen Screen Rules
Kitchen sees:

✅ Only food items
✅ No prices
✅ Real-time updates

🔹 Kitchen Actions
Preparing
Pending Kitchen → Preparing
Ready
Preparing → Ready To Serve
🍽️ 2. Serve System (IMPORTANT)

When kitchen marks READY:

👉 Order moves to:

"To Serve" Queue

Used by waiter.

🔹 Waiter Serve Action
Ready To Serve → Served
🔥 IMPORTANT BUSINESS LOGIC

After SERVED:

❌ DO NOT CLOSE ORDER
❌ DO NOT AUTO PAYMENT

Because customer may:

Order more items
Stay at table
Request additional food
🔄 3. Ongoing Payment State (VERY IMPORTANT)

After serve:

Served → Ongoing Payment

Order remains ACTIVE.

🔹 Ongoing Orders Screen

Shows:

Table
Customer
Running Total
Last Order Time
✏️ 4. Add More Items After Serve (CRITICAL)

This is real restaurant logic.

🔹 Flow

Customer says:

"Add 1 more Coke"

System should:

✅ Open existing order
✅ Add new items
✅ Send ONLY new items to kitchen

🔹 Important Logic

OLD items:

Already Served

NEW items:

Pending Kitchen
🔹 Kitchen Receives ONLY New Items

Example:

EXISTING:
2 Burger (served)

NEW:
1 Coke

Kitchen screen only shows:

1 Coke
🧠 5. KOT (Kitchen Token) Logic

Every update creates NEW kitchen token.

Example:

KOT-101 → Original order
KOT-102 → Added items
KOT-103 → Additional order
🔹 Why This Is Important

Without separate KOT:

❌ Kitchen confusion
❌ Duplicate preparation
❌ Wrong serving

💰 6. Payment Flow

When customer asks for bill:

Ongoing Payment → Generate Bill
🔹 Bill Includes

✅ All served items
✅ All additional items
✅ Tax
✅ Discount

🔹 Payment Status
Unpaid
Partial
Paid
🔹 Final Completion
Paid → Completed

Then:

✅ Free table
✅ Close order

🗄️ 7. Database Flow (IMPORTANT)
🔹 orders

Main order record.

🔹 order_items

Each item has:

status
kot_id
served_qty
🔹 kitchen_tokens (NEW TABLE)
id
order_id
token_no
status
created_at
🔹 kitchen_token_items
id
kitchen_token_id
order_item_id
quantity
status
⚡ 8. Correct Real Restaurant Workflow
1. Create Order
2. Add Items
3. Place Order
4. Send to Kitchen
5. Kitchen Prepare
6. Ready To Serve
7. Waiter Serve
8. Keep in Ongoing
9. Add More Items (optional)
10. Repeat Kitchen Flow
11. Generate Final Bill
12. Payment
13. Complete Order
🖥️ 9. UI Sections Needed
🔹 POS Screen
Create/update order
Add items
Ongoing orders
🔹 Kitchen Display Screen
Live KOT queue
Prepare/Ready buttons
🔹 To Serve Screen
Ready items
Serve button
🔹 Ongoing Payment Screen
Running tables/orders
Pending payment
🔥 10. Most Important Engineering Rule
NEVER lock order after first serve.

Real restaurants require:

✅ Continuous ordering
✅ Partial serving
✅ Multiple kitchen rounds

🚀 Final Architecture Insight

Your system is now:

Restaurant Workflow Engine ✅

Not just:

Simple Billing Software ❌

Because now you support:

Kitchen operations
Live serving
Continuous ordering
Real dining behavior
Multiple KOT cycles