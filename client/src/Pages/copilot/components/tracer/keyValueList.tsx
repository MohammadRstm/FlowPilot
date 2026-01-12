import { TypedArrayItem } from "./typedArrayItem";
import { TypedLine } from "./typedLine";

function renderItem(item: any, hideId = true) {
  if (item && typeof item === "object") {
    if ("id" in item && "description" in item) {
      return (
        <div className="kv-object">
          {!hideId && <strong>{item.id}</strong>}
          <TypedLine value={item.description} />
        </div>
      );
    }

    if ("requirement_id" in item && "message" in item) {
      return (
        <div className={`kv-object ${item.severity}`}>
          {!hideId && <strong>{item.requirement_id}</strong>}
          <TypedLine value={item.message} />
          {item.severity && <span className="severity">{item.severity}</span>}
        </div>
      );
    }

    return <KeyValueList data={item} />;
  }

  return <TypedLine value={String(item)} />;
}


function renderValue(value: any) {
  if (value && typeof value === "object" && !Array.isArray(value)) {
    return <KeyValueList data={value} />;
  }

  if (Array.isArray(value)) {
    return (
      <ul>
        {value.map((v, i) => (
          <TypedArrayItem key={i}>{renderItem(v)}</TypedArrayItem>
        ))}
      </ul>
    );
  }

  return <TypedLine value={String(value)} />;
}

export function KeyValueList({ data }: { data: any }) {
  return (
    <div className="kv">
      {Object.entries(data).map(([key, value]) => (
        <div key={key} className="kv-row">
          <strong>{key}</strong>
          <div className="kv-value">{renderValue(value)}</div>
        </div>
      ))}
    </div>
  );
}
