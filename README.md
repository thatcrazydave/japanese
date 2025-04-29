# Japanese Language Learning Application

This project consists of three main components:

1. **Frontend** (japanese-project): React application for the user interface
2. **Proxy Server** (proxy-server): Node.js server that forwards requests to LM Studio 
3. **Backend API** (japanese-ai-backend): FastAPI Python service for translations

## Project Structure

```
root/
├── japanese-project/     # React Frontend (port 3000)
├── proxy-server/         # Node.js Proxy Server (port 8080)
├── japanese-ai-backend/  # Python FastAPI Backend (port 8000)
├── start-services.bat    # Startup script for Windows
└── start-services.sh     # Startup script for Unix/Linux/Mac
```

## Prerequisites

- Node.js and npm installed
- Python 3.8+ installed with pip
- LM Studio running locally on port 1234 (for AI completions)

## Setup Instructions

### 1. Install Frontend Dependencies

```bash
cd japanese-project
npm install
```

### 2. Install Proxy Server Dependencies

```bash
cd proxy-server
npm install
```

### 3. Install Backend Dependencies

```bash
cd japanese-ai-backend
pip install -r requirements.txt
```

## Running the Application

### Option 1: Using the Startup Scripts

#### Windows:
```
start-services.bat
```

#### Unix/Linux/Mac:
```bash
chmod +x start-services.sh  # Make the script executable (first time only)
./start-services.sh
```

### Option 2: Manual Startup

Start each component in a separate terminal:

1. **Start the Proxy Server**:
   ```bash
   cd proxy-server
   npm start
   ```

2. **Start the Backend**:
   ```bash
   cd japanese-ai-backend
   python main.py
   ```

3. **Start the Frontend**:
   ```bash
   cd japanese-project
   npm start
   ```

## Component Communication

- Frontend → Proxy (port 8080): For AI completions and chat
- Frontend → Backend (port 8000): For translations
- Proxy → LM Studio (port 1234): For AI model access

## Important Notes

- Ensure LM Studio is running and configured to serve API requests on port 1234
- The frontend's `.env` file contains configuration for backend URL
- The proxy server forwards requests to LM Studio for AI completions 