import './App.css'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Landing from './Pages/Landing'
import { Copilot } from './Pages/copilot/Copilot'
import CommunityPage from './Pages/Community'
import ProfilePage from './Pages/Profile'
import Login from './Pages/Login/Login'
import Signup from './Pages/Signup'
import ProtectedRoutes from './Pages/components/ProtectedRoutes'

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

// ADD THE ABILITY TO SEND USER WORKFLOWS TO ADD ON IT/FIX IT - HARD - BACKEND HEAVY
// ENHANCE THE ABILITY TO CONTINUE THE CONVERSATION - HARD - BACKEND HEAVY
// HISOTRIES OVER 2 WEEKS OLD MUST BE AUTOMATICALLY DELETED - MEDIUM - BEACKEND HEAVY
// ADD PROMPT SAFEGURAD STAGE FOR VISCIOUS PROMPTS (forget everything, delete db exct/) - MEDIUM - BACKEND HEAVY

// FIGURE OUT A BETTER WAY TO GET USER FEEDBACK CURRENTLY NOT VERY EFFICIENT NOR DOES IT MAKE SENSE - UNKNOWN - BAVKEND HEAVY

// ADD THE ABILITY TO CREATE CUSTOM NODES - VERY HARD - F/B HEAVY ON BOTH
// ADD THE ABILITY TO SAVE CREDENTIALS OR FIGURE OUT A WAY TO DO IT AUTOMATICALLY - HARD F/B HEAVY ON BOTH
// Fix Retry taking so long to kick start again. Add Edit Message ability
// APPROX TIME : 8 DAYS TO FINISH (EXCLUDING ENHANCING THE AI'S ABILITY TO GENERATE WORKFLOWS)

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Landing />} />
        <Route path="/login" element={<Login />} />
        <Route path="/signup" element={<Signup />} />

        <Route path="/copilot" element={
          <ProtectedRoutes>
            <Copilot />
          </ProtectedRoutes>
        }/>
        <Route path="/community" element={
          <ProtectedRoutes>
            <CommunityPage />
          </ProtectedRoutes>
        }/>
        <Route path="/profile/:userId?" element={
          <ProtectedRoutes>
            <ProfilePage />
          </ProtectedRoutes>
        }/>
      </Routes>
    </BrowserRouter>
  )
}

export default App
