import { TypedLine } from "./TypedLine";
import { TypedList } from "./TypedList";

function renderItem(item: any, hideId = true) {
  if (item && typeof item === "object") {
    if ("id" in item && "description" in item) {
      return (
        <div className="kv-object">
          {!hideId && <TypedLine value={String(item.id)} />}
          <TypedLine value={item.description} />
        </div>
      );
    }

    if ("requirement_id" in item && "message" in item) {
      return (
        <div className={`kv-object ${item.severity}`}>
          {!hideId && (
            <TypedLine value={String(item.requirement_id)} />
          )}
          <TypedLine value={item.message} />
          {item.severity && (
            <TypedLine value={item.severity} />
          )}
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
        <TypedList
        items={value}
        renderItem={(item) => renderItem(item)}
        />
    );
  }

  return <TypedLine value={String(value)} />;
}

export function KeyValueList({ data }: { data: any }) {
  return (
    <div className="kv">
      {Object.entries(data).map(([key, value]) => (
        <div key={key} className="kv-row">
          <strong>
            <TypedLine value={key} />
          </strong>
          <div className="kv-value">{renderValue(value)}</div>
        </div>
      ))}
    </div>
  );
}

