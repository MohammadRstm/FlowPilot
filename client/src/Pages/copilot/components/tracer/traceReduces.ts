export function applyTraceEvent(state: any, trace: any){
  let path: string;
  let value: any;
  
  if("path" in trace){// first format we send data in
    path = trace.path;
    value = trace.value;
  }else{ // second format
    path = trace.type;
    value = trace.payload;
  }

  if (!path) return;

  const parts = path.split(".");// judment.score -> ["judment" , "score"]
  let cursor = state;// we will walk through the state untill we figure out where to place the value

  for (let i = 0; i < parts.length; i++) {
    const part = parts[i];
    const isLast = i === parts.length - 1;
    const isItem = part === "item";

    if(isItem){// hanldes items in array
      // push into array
      if (!Array.isArray(cursor)) {
        cursor = []; 
        // attach it back to the parent
        const parentPath = parts.slice(0, i).join(".");
        const parentCursor = getCursor(state, parentPath); 
        parentCursor[parts[i-1]] = cursor;
      }
      cursor.push(value);// push each item into its respective array
      return;
    }

    if(isLast){// if we are at the last part then assign the value directly
      cursor[part] = value;
      return;
    }

    if(!(part in cursor)){// if we don't find the part in cursor then create a new object for it, this makes it all dynamic
      cursor[part] = {};
    }

    cursor = cursor[part];
  }
}


function getCursor(state: any, path: string) {
  if (!path) return state;
  return path.split(".").reduce((acc, key) => acc[key], state);
}
