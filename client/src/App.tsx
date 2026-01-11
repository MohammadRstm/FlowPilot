import './App.css'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Landing from './Pages/Landing'
import { Copilot } from './Pages/copilot/Copilot'

/**
 * TIME CALENDER:
 * COPILOT PAGE REQUIRED 8 MORE DAYS MAX TO FINISH ALL FEATURES -> INLUDES HUGE ARCHITECTURAL CAHNGES IN THE BACKEDN
 * 
 * POSTS & PROFILE PAGES NEED 4 DAYS , 5 DAYS MAXIMUM
 * 
 * ENHANCING N8N AI GENERATION NEEDS 5 DAYS OF WORK
 * 
 * CUSTOM NODE GENERATION (IF TIME ALLOWS IT) -> 4 DAYS
 * 
 * WITH NO ADD ONS WE HAVE : 17 DAYS
 * 
 * WITH ADD ONS : 21 DAYS
 * 
 * AT THE TIME I'M WRITING THIS I'M LEFT WITH 19 DAYS
 */

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
