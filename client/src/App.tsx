import './App.css'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Landing from './Pages/Landing'
import { Copilot } from './Pages/copilot/Copilot'

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Landing />} />
        <Route path="/copilot" element={<Copilot />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
