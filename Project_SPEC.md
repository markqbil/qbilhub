# **QBIL HUB \- MASTER PROJECT SPECIFICATION**

## **1\. PROJECT OVERVIEW**

Qbil Hub is a centralized B2B document exchange and data integration platform for the commodity trading sector. It replaces email-based workflows with a structured "Inbox" and uses AI to map disparate product codes between trading partners.  
Goal: Build a commercially viable MVP in a 6-month timeline using an implementation-first, active-learning strategy.

## **2\. TECHNICAL ARCHITECTURE (HYBRID)**

The system consists of two main components communicating via a message bus.

### **A. Core Application (The "Controller")**

* **Role:** System of record, UI rendering, Business Logic, User Auth.  
* **Language/Framework:** PHP / Symfony 6.4+ (or 7.0).  
* **API Layer:** API Platform.  
* **Database:** PostgreSQL (Crucial: Use JSONB for storing flexible contract data from different tenants).  
* **Frontend:** Twig templates augmented with Vue.js for dynamic components (Inbox, Split-View Mapping).  
* **Real-time:** Symfony Mercure (Server-Sent Events) for Inbox notifications.  
* **Messaging:** Symfony Messenger (RabbitMQ or Redis) to offload tasks to the Python service.

### **B. Intelligence Microservice (The "Brain")**

* **Role:** Entity Resolution (Product Matching) and Schema Extraction.  
* **Language:** Python / FastAPI.  
* **Key Libraries:**  
  * dedupe (Python) for probabilistic record linkage/fuzzy matching.  
  * OpenAI API (via LangChain) for semantic schema mapping (parsing "header" info).  
* **Communication:** Consumes jobs from the Symfony Messenger queue, processes them, and returns results.

## **3\. CORE FEATURES & FUNCTIONAL REQUIREMENTS**

### **3.1 The Hub Inbox (MVP Priority)**

* **UI:** A new menu item in the existing sidebar.  
* **Features:**  
  * Red notification badge for unread items (real-time via Mercure).  
  * Table view showing: Status (New/Mapping/Processed), Source (Partner Logo), Document Link, Timestamp.  
  * "Entity-Aware": Items are not just emails; they are structured objects linking a Tenant to a PurchaseContract.

### **3.2 Connectivity & Multi-Tenancy**

* **Global Directory:** System maintains a directory of Tenants (Subsidiaries).  
* **Activation:** Users must explicitly "Opt-in" to activate Qbil Hub (sets is\_hub\_active \= true).  
* **Mapping:** Admin maps internal "Relations" to external "TenantIDs".  
* **Intelligent Routing:** If a Relation is connected and the contact is active, default to "Send via Hub" instead of Email.

### **3.3 Shared Mailbox & Delegation**

* **Delegation Model:** Many-to-Many user\_delegations (User A grants access to User B).  
* **View Filter:** Dropdown to switch between "My Inbox", "All Accessible", or "Specific Colleague".  
* **Audit:** Log who actioned a document ("Actioned by B on behalf of A").

### **3.4 The "Split-View" Mapping UI (Complex Component)**

* **Layout:** Vertical split screen.  
  * **Left (Source):** PDF Viewer or extracted raw data (JSON tree).  
  * **Right (Target):** Standard Qbil Contract Entry Form.  
* **AI Interaction:**  
  * Fields pre-filled by the Python service.  
  * Confidence scores displayed (Green \>90%, Yellow \<90%).  
  * **Active Learning:** When a user corrects a field, the tuple {Source, Target, Correction} is sent back to the Python service to retrain the dedupe model.

## **4\. DATA STRATEGY**

* **Storage:** Incoming contracts stored as JSONB in PostgreSQL to handle schema variability (Tenant A sends mat\_id, Tenant B sends product\_code).  
* **Mapping Strategy:**  
  * **Schema (Columns):** LLM determines that mat\_id maps to Product.  
  * **Entity (Rows):** dedupe library determines that "WPC 80" maps to "Whey Protein Conc. 80%".

## **5\. CODING GUIDELINES (FOR AI GENERATION)**

1. **Symfony Best Practices:** Use strict types, Dependency Injection, and Attributes for routing/ORM. Use the Repository pattern.  
2. **API Platform:** Use API Resources for standard CRUD, but custom Controllers/DTOs for complex actions (like "Process Mapping").  
3. **Frontend:** Write modular Vue.js components for the Inbox and Mapping screens. Ensure they can be mounted within the Twig templates.  
4. **Error Handling:** Never fail silently. If the Python service is down, queue the job and notify the user of a delay.  
5. **Security:** Implement Row-Level Security logic in application code (Tenants cannot see each other's data/mappings).

## **6\. IMMEDIATE TASK**

(Opmerking voor gebruiker: Vul hier in wat je als eerste wilt bouwen, bijv. "Scaffold the Symfony Entity for 'ReceivedDocument' with JSONB fields and the API Platform resource.")