// ChatContext removed — chat state managed locally within AgencyDetail page.
import { createContext, useContext, type ReactNode } from 'react';

interface ChatContextValue {}
const ChatCtx = createContext<ChatContextValue | null>(null);

export function ChatProvider({ children }: { children: ReactNode }) {
  return <ChatCtx.Provider value={{}}>{children}</ChatCtx.Provider>;
}

export function useChat(): ChatContextValue {
  const ctx = useContext(ChatCtx);
  if (!ctx) throw new Error('useChat must be inside <ChatProvider>');
  return ctx;
}
