import { getNodeSchema } from "../tools/schema";

export async function gerNodeSchemas(node){
    return getNodeSchema(node)
}